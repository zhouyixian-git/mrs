<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/16 0016
 * Time: 19:24
 */

namespace app\api\controller;


use think\Db;
use think\Request;

class Withdraw extends Base
{

    /**
     * 生成提现记录
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function createWithDraw(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');
            $withdraw_integral = $request->post('withdraw_integral');
            if (empty($user_id)) {
                echo $this->errorJson(1, '缺少关键参数user_id');
                exit;
            }
            if (empty($withdraw_integral)) {
                echo $this->errorJson(1, '缺少关键参数withdraw_amount');
                exit;
            }

            $user = Db::table('mrs_user')
                ->field('able_integral,frozen_integral,used_integral')
                ->where('user_id', '=', $user_id)
                ->find();
            if (empty($user)) {
                echo $this->errorJson(1, '用户信息不存在');
                exit;
            }

            //提现规则
            //todo
            if ($withdraw_integral > $user['able_integral']) {
                echo $this->errorJson(1, '可提现积分不足');
                exit;
            }

            //提现手续费率 %
            $where = [];
            $where[] = ['setting_code', '=', 'withdraw_rate'];
            $withdraw_rate = Db::table('mrs_system_setting')->where($where)->value('setting_value');
            $withdraw_rate = empty($withdraw_rate) ? 0 : $withdraw_rate;

            $where = [];
            $where[] = ['setting_code', '=', 'integral'];
            $integral = Db::table('mrs_system_setting')->where($where)->value('setting_value');
            $integral = empty($integral) ? 80 : $integral;

            $withdraw_amount = bcmul($withdraw_integral, $integral * 0.01, 2);

            $withdraw_fee = 0; //手续费
            if (!empty($withdraw_rate)) {
                $withdraw_fee = bcmul($withdraw_amount, $withdraw_rate * 0.01, 2);
            }

            $withdraw_payed = bcsub($withdraw_amount, $withdraw_fee, 2); //到账金额、
            $withdraw_sn = date('YmdHis', time()) . rand(100000, 999999);

            $integral_before = $user['able_integral'];
            $integral_after = bcsub($integral_before, $withdraw_integral, 2);

            $data['withdraw_sn'] = $withdraw_sn;
            $data['user_id'] = $user_id;
            $data['withdraw_amount'] = $withdraw_amount;
            $data['withdraw_fee'] = $withdraw_fee;
            $data['withdraw_payed'] = $withdraw_payed;
            $data['integral_used'] = $withdraw_integral;
            $data['integral_before'] = $integral_before;
            $data['integral_after'] = $integral_after;
            $data['status'] = 1;
            $data['create_time'] = time();

            Db::startTrans();
            try {
                $res1 = Db::table('mrs_withdraw')->insert($data);

                $userData['able_integral'] = bcsub($user['able_integral'], $withdraw_integral, 2);
                $userData['frozen_integral'] = bcadd($user['frozen_integral'], $withdraw_integral, 2);
                //$userData['used_integral'] = bcadd($user['used_integral'], $withdraw_integral, 2);
                $res2 = Db::table('mrs_user')->where('user_id', '=', $user_id)->update($userData);

                if ($res1 && $res2) {
                    Db::commit();
                    echo $this->successJson();
                    exit;
                } else {
                    Db::rollback();
                    echo $this->errorJson(1, '提现失败');
                    exit;
                }
            } catch (Exception $e) {
                Db::rollback();
                echo $this->errorJson(1, '提现异常');
                exit;
            }
        }
    }

}