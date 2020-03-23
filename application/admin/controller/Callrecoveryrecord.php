<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/18 0018
 * Time: 11:33
 */

namespace app\admin\controller;


use think\Db;
use think\Request;

class Callrecoveryrecord extends Base
{

    /**
     * 上门回收记录
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request){
        $master_name = $request->param('master_name');
        $master_phone_no = $request->param('master_phone_no');
        $user_phone_no = $request->param('user_phone_no');
        $address = $request->param('address');
        $where = [];
        if (!empty($master_name)) {
            $where[] = ['t1.master_name', '=', $master_name];
        }
        if (!empty($address)) {
            $where[] = ['t1.address', 'like', "%$address%"];
        }
        if (!empty($master_phone_no)) {
            $where[] = ['t1.master_phone_no', '=', $master_phone_no];
        }
        if (!empty($user_phone_no)) {
            $where[] = ['t1.user_phone_no', '=', $user_phone_no];
        }

        $recordList = Db::table('mrs_call_recovery_record')
            ->alias('t1')
            ->field('t1.*,t2.user_name')
            ->leftJoin('mrs_user t2', 't1.user_id = t2.user_id')
            ->order('t1.create_time desc')
            ->where($where)
            ->paginate(8, false, [ 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $recordList->render();
        $count = Db::table('mrs_call_recovery_record')->alias('t1')->count();

        $this->assign('master_name', $master_name);
        $this->assign('address', $address);
        $this->assign('master_phone_no', $master_phone_no);
        $this->assign('user_phone_no', $user_phone_no);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('recordList', $recordList);
        return $this->fetch();
    }

    /**
     * 受理
     * @param Request $request
     * @return mixed
     */
    public function accept(Request $request){
        if($request->isPost()){
            $record_id = $request->post('record_id');
            $accept_remark = $request->post('accept_remark');

            $data = array();
            $data['accept_status'] = 2;
            $data['accept_remark'] = $accept_remark;
            Db::table('mrs_call_recovery_record')
                ->where('call_recovery_record_id', '=', $record_id)
                ->update($data);

            echo $this->successJson();
            exit;
        }

        $record_id = $request->get('record_id');
        $this->assign('record_id', $record_id);
        return $this->fetch();
    }

}
