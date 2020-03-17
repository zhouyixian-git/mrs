<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/7 0007
 * Time: 20:23
 */

namespace app\admin\controller;


use think\Db;

class Index extends Base
{
    public function index()
    {
        return $this->fetch();
    }

    public function home()
    {
        //订单金额
        $orderTotal =  Db::table('mrs_orders')->where('pay_status', '=', 2)->sum('order_amount');
        //订单量
        $orderNum =  Db::table('mrs_orders')->count();
        //待提现金额
        $waitAmouont = Db::table('mrs_withdraw')->where('status', '=', 1)->sum('withdraw_amount');
        //已提现金额
        $alreadyAmount = Db::table('mrs_withdraw')->where('status', '=', 2)->sum('withdraw_amount');

        //订单量
        $sql = "select FROM_UNIXTIME(create_time, '%Y-%m-%d') days,count(*) count from ";
        $sql .= "(select * from mrs_orders where DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= FROM_UNIXTIME(create_time, '%Y-%m-%d')) as tt ";
        $sql .= " group by days ";
        $orderNumResult = Db::query($sql);
        $orderNumXs = [];
        $orderNumYs = [];
        foreach ($orderNumResult as $v){
            $orderNumXs[] = $v['days'];
            $orderNumYs[] = $v['count'];
        }

        //回收记录
        $sql = "select FROM_UNIXTIME(create_time, '%Y-%m-%d') days,count(*) count from ";
        $sql .= "(select * from mrs_call_recovery_record where DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= FROM_UNIXTIME(create_time, '%Y-%m-%d')) as tt ";
        $sql .= " group by days ";
        $recoveryNumResult = Db::query($sql);
        $recoveryXs = [];
        $recoveryYs = [];
        foreach ($recoveryNumResult as $v){
            $recoveryXs[] = $v['days'];
            $recoveryYs[] = $v['count'];
        }

        //提现记录
        $sql = "select FROM_UNIXTIME(create_time, '%Y-%m-%d') days,count(*) count from ";
        $sql .= "(select * from mrs_withdraw where DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= FROM_UNIXTIME(create_time, '%Y-%m-%d')) as tt ";
        $sql .= " group by days ";
        $withdrawNumResult = Db::query($sql);
        $withdrawXs = [];
        $withdrawYs = [];
        foreach ($withdrawNumResult as $v){
            $withdrawXs[] = $v['days'];
            $withdrawYs[] = $v['count'];
        }


        $this->assign('orderTotal', $orderTotal > 0 ? $orderTotal : 0);
        $this->assign('orderNum', $orderNum > 0 ? $orderNum : 0);
        $this->assign('waitAmouont', $waitAmouont > 0 ? $waitAmouont : 0);
        $this->assign('alreadyAmount', $alreadyAmount > 0 ? $alreadyAmount : 0);
        $this->assign('orderNumXs', $orderNumXs);
        $this->assign('orderNumYs', $orderNumYs);
        $this->assign('recoveryXs', $recoveryXs);
        $this->assign('recoveryYs', $recoveryYs);
        $this->assign('withdrawXs', $withdrawXs);
        $this->assign('withdrawYs', $withdrawYs);
        return $this->fetch();
    }
}
