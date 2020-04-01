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
        if (!empty($withdraw_sn)) {
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

        $this->assign('status', $status);
        $this->assign('withdraw_sn', $withdraw_sn);
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

            if ($data['status'] == 5 && empty($data['auth_remark'])) {
                echo $this->errorJson(0, '请填写审核备注');
                exit;
            }

            $withdraw = Db::table('mrs_withdraw')
                ->field('t1.user_id,t1.withdraw_sn,t1.withdraw_amount,t1.integral_used,t2.able_integral,t2.frozen_integral,t2.used_integral,t2.open_id')
                ->alias('t1')
                ->leftJoin('mrs_user t2', 't1.user_id = t2.user_id')
                ->where('t1.withdraw_id', '=', $withdraw_id)
                ->find();

            if (empty($withdraw)) {
                echo $this->errorJson(0, '未找到提现记录');
                exit;
            }

            Db::startTrans();
            if ($data['status'] == 5) { //提现失败，将提现金额返还用户
                $userData = [];
                $userData['able_integral'] = bcadd($withdraw['able_integral'], $withdraw['integral_used'], 2);
                $userData['frozen_integral'] = bcsub($withdraw['frozen_integral'], $withdraw['integral_used'], 2);
                Db::table('mrs_user')->where('user_id', '=', $withdraw['user_id'])->update($userData);

                //生成积分流水
                $integralData['user_id'] = $withdraw['user_id'];
                $integralData['integral_value'] = $withdraw['integral_used'];
                $integralData['type'] = 1;
                $integralData['action_desc'] = '提现失败积分退回';
                $integralData['create_time'] = time();
                Db::table('mrs_integral_detail')->insert($integralData);

                $data['admin_id'] = parent::$_ADMINID;
                $res = Db::table('mrs_withdraw')->where('withdraw_id', '=', $withdraw_id)->update($data);

                if ($res) { //更新成功
                    Db::commit();
                    echo $this->successJson();
                    exit;
                }else{
                    Db::rollback();
                    echo $this->errorJson(1, '操作失败');
                    exit;
                }
            }

            $data['admin_id'] = parent::$_ADMINID;
            $res = Db::table('mrs_withdraw')->where('withdraw_id', '=', $withdraw_id)->update($data);
            if ($res) { //审核通过
                Db::commit();

                //todo  审核通过，调用微信企业付款到个人接口
                $wechatModel = new \app\admin\model\Wechat();
                $param['partner_trade_no'] = $withdraw['withdraw_sn'];
                $param['amount'] = $withdraw['withdraw_amount'];
                $param['openid'] = $withdraw['open_id'];
                $param['desc'] = '用户提现';
                $result = $wechatModel->transfers($param);

                if (isset($result['errcode'])) {
                    Db::startTrans();
                    //提现失败，更新提现记录表数据
                    $data = [];
                    $data['status'] = 3;
                    $data['fail_remark'] = $result['errmsg'];
                    $data['admin_id'] = parent::$_ADMINID;
                    Db::table('mrs_withdraw')->where('withdraw_id', '=', $withdraw_id)->update($data);

                    $userData = [];
                    $userData['able_integral'] = bcadd($withdraw['able_integral'], $withdraw['integral_used'], 2);
                    $userData['frozen_integral'] = bcsub($withdraw['frozen_integral'], $withdraw['integral_used'], 2);
                    Db::table('mrs_user')->where('user_id', '=', $withdraw['user_id'])->update($userData);

                    //生成积分流水
                    $integralData['user_id'] = $withdraw['user_id'];
                    $integralData['integral_value'] = $withdraw['integral_used'];
                    $integralData['type'] = 1;
                    $integralData['action_desc'] = '提现失败积分退回';
                    $integralData['create_time'] = time();
                    Db::table('mrs_integral_detail')->insert($integralData);

                    Db::commit();
                    echo $this->errorJson(1, $result['errmsg']);
                    exit;
                }

                //$result['payment_time'] = time(); //todo 测试用的，到时候要去掉
                Db::startTrans();
                try {
                    // 更新提现记录表状态
                    $data = [];
                    $data['status'] = 2;
                    $data['fail_remark'] = '提现成功';
                    $data['admin_id'] = parent::$_ADMINID;
                    $data['payed_time'] = strtotime($result['payment_time']);
                    $res1 = Db::table('mrs_withdraw')->where('withdraw_id', '=', $withdraw_id)->update($data);

                    //更新用户资产
                    $userData = [];
                    $userData['frozen_integral'] = bcsub($withdraw['frozen_integral'], $withdraw['integral_used'], 2);
                    $userData['used_integral'] = bcadd($withdraw['used_integral'], $withdraw['integral_used'], 2);
                    $res2 = Db::table('mrs_user')->where('user_id', '=', $withdraw['user_id'])->update($userData);

                    if($res1 && $res2) {
                        Db::commit();
                        echo $this->successJson();
                        exit;
                    }else{
                        Db::rollback();
                        echo $this->errorJson(1, '审核成功，提现失败');
                        exit;
                    }
                } catch (Exception $e) {
                    Db::rollback();
                    echo $this->errorJson(1, '审核成功，提现异常');
                    exit;
                }
            } else {
                Db::rollback();
                echo $this->errorJson(0, '审核失败');
                exit;
            }
        }

        $withdraw_id = $request->get('withdraw_id');
        $this->assign('withdraw_id', $withdraw_id);
        return $this->fetch();
    }

}
