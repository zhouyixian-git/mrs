<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/16 0016
 * Time: 21:59
 */

namespace app\admin\controller;


use think\Db;
use think\Request;

class Withdraw extends Base
{

    /**
     * 提现记录列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $withdraw_sn = $request->param('withdraw_sn');
        $status = $request->param('status');
        $where = [];
        if (!empty($status)) {
            $where[] = ['t1.withdraw_sn', 'like', "%$withdraw_sn%"];
        }
        if (!empty($status)) {
            $where[] = ['t1.status', '=', $status];
        }

        $withdrawList = Db::table('mrs_withdraw')
            ->alias('t1')
            ->field('t1.*,t2.user_name,t3.admin_name')
            ->leftJoin('mrs_user t2', 't1.user_id = t2.user_id')
            ->leftJoin('eas_admin t3', 't1.admin_id = t3.admin_id')
            ->where($where)
            ->order('t1.create_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $withdrawList->render();
        $count = Db::table('mrs_withdraw')->alias('t1')->where($where)->count();

        $this->assign('status', $status);
        $this->assign('withdraw_sn', $withdraw_sn);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('withdrawList', $withdrawList);
        return $this->fetch();
    }

    /**
     * 提现审核
     * @param Request $request
     * @return mixed
     */
    public function auth(Request $request)
    {
        if ($request->isPost()) {
            $withdraw_id = $request->post('withdraw_id');
            $data['status'] = $request->post('status');
            $data['auth_remark'] = $request->post('auth_remark');

            if($data['status'] == 5 && empty($data['auth_remark'])){
                echo $this->errorJson(0, '请填写审核备注');
                exit;
            }

            Db::table('mrs_withdraw')->where('withdraw_id', '=', $withdraw_id)->update($data);


            //todo  审核通过，调用微信企业付款到个人接口




            echo $this->successJson();
            exit;
        }

        $withdraw_id = $request->get('withdraw_id');
        $this->assign('withdraw_id', $withdraw_id);
        return $this->fetch();
    }

}
