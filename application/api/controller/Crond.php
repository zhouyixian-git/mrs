<?php
namespace app\api\controller;

use function Sodium\crypto_generichash_update;
use think\Db;
use think\Exception;
use think\Request;

class Crond extends Base
{
    public function index()
    {
        //自动收货调度
        echo $this->autoConfirmOrder();

        //自动取消订单调度
        //echo $this->autoCancelOrder();

        //自动失效积分
        echo $this->overtimeintegral();
        exit;
    }

    //自动收货调度
    private function autoConfirmOrder(){
        $confirmOrderCount = 0;

        $confirmSetting = Db::table('mrs_system_setting')->where('setting_code','=','auto_confirm_order_time')->find();

        $autoConfirmOrderTime = $confirmSetting['setting_value'];

        $where = array();
        $where[] = ['order_status','=','3'];
        $where[] = ['shipping_status','=','2'];
        $where[] = ['refund_status','=','1'];
        $where[] = ['sales_status','=','1'];
        $where[] = ['shipping_time','>','0'];
        $where[] = ['shipping_time','>=', time() - 86400*$autoConfirmOrderTime];

        $orders = Db::table('mrs_orders')->where($where)->select();
        if(is_array($orders) && count($orders) > 0 ){
            Db::startTrans();
            try{
                $orderArr = array();
                foreach($orders as $k=>$order){
                    $orderArr[] = $order['order_id'];
                    $confirmOrderCount++;

                    //生成订单动作表
                    $actionData['order_id'] = $order['order_id'];
                    $actionData['action_name'] = '系统自动收货';
                    $actionData['action_user_id'] = 0;
                    $actionData['action_user_name'] = '系统';
                    $actionData['action_remark'] = '系统自动收货';
                    $actionData['create_time'] = time();
                    Db::table('mrs_order_action')->insert($actionData);
                }
                $orderIdStr = implode(',',$orderArr);

                //更新订单数据
                $updateData = array();
                $updateData['order_status'] = '4';
                $updateData['shipping_status'] = '3';
                $updateData['confirm_time'] = time();

                Db::table('mrs_orders')->where('order_id','in',$orderIdStr)->update($updateData);

                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                $confirmOrderCount = 0;
            }
        }
        return 'Auto confirm order shipping status count：'.$confirmOrderCount.'<hr>';

    }

    //自动取消订单调度
    private function autoCancelOrder(){
        $cancelOrderCount = 0;

        $cancelSetting = Db::table('mrs_system_setting')->where('setting_code','=','auto_cancel_order_time')->find();

        $autoCancelOrderTime = $cancelSetting['setting_value'];

        $where = array();
        $where[] = ['order_status','=','1'];
        $where[] = ['pay_status','=','1'];
        $where[] = ['create_time','>=', time() - 3600*$autoCancelOrderTime];

        $orders = Db::table('mrs_orders')->where($where)->select();
        if(is_array($orders) && count($orders) > 0 ){
            Db::startTrans();
            try{
                $orderArr = array();
                foreach($orders as $k=>$order){
                    $orderArr[] = $order['order_id'];
                    $cancelOrderCount++;

                    //生成订单动作表
                    $actionData['order_id'] = $order['order_id'];
                    $actionData['action_name'] = '系统自动取消订单';
                    $actionData['action_user_id'] = 0;
                    $actionData['action_user_name'] = '系统';
                    $actionData['action_remark'] = '系统自动取消订单';
                    $actionData['create_time'] = time();
                    Db::table('mrs_order_action')->insert($actionData);
                }
                $orderIdStr = implode(',',$orderArr);

                //更新订单数据
                $updateData = array();
                $updateData['order_status'] = '5';
                $updateData['cancel_time'] = time();

                //积分退回
                if ($order['pay_type'] == 2 || $order['pay_type'] == 3) { //支付方式为微信支付+积分或者是积分抵扣
                    $integral = bcdiv($order['integral_amount'], $order['integral_rate'], 2);

                    //更新用户积分
                    Db::table('mrs_user')->where('user_id', '=', $order['user_id'])->setInc('able_integral', $integral);
                    Db::table('mrs_user')->where('user_id', '=', $order['user_id'])->setDec('used_integral', $integral);

                    //增加对应用户积分明细
                    $integralDetail = array();
                    $integralDetail['user_id'] = $order['user_id'];
                    $integralDetail['integral_value'] = $integral;
                    $integralDetail['type'] = 1;
                    $integralDetail['action_desc'] = '用户取消订单积分退回';
                    $integralDetail['invalid_time'] = time() + 86400 * 180;
                    $integralDetail['create_time'] = time();
                    Db::table('mrs_integral_detail')->insert($integralDetail);

                }

                Db::table('mrs_orders')->where('order_id','in',$orderIdStr)->update($updateData);

                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                $cancelOrderCount = 0;
            }
        }
        return 'Auto cancel order status count：'.$cancelOrderCount.'<hr>';
    }

    function overtimeintegral(){
        $overtimeIntegral = 0;

        $where = array();
        $where[] = ['invalid_time','>',time()];
        $where[] = ['is_overtime','=','0'];
        $where[] = ['type','=','1'];

        $integralRecords = Db::table('mrs_integral_detail')->where($where)->select();

        if(is_array($integralRecords) && count($integralRecords) > 0 ){
            Db::startTrans();
            try{
                foreach($integralRecords as $k=>$record){
                    $user = Db::table("mrs_user")->where('user_id','=',$record['user_id'])->find();
                    if($user['able_integral'] >= $record['integral_value']){
                        //增加对应用户积分明细记录
                        $integralDetail = array();
                        $integralDetail['user_id'] = $record['user_id'];
                        $integralDetail['integral_value'] = $record['integral_value'];
                        $integralDetail['type'] = 2;
                        $integralDetail['action_desc'] = '积分失效，减少对应积分';
                        $integralDetail['create_time'] = time();
                        Db::table('mrs_integral_detail')->insert($integralDetail);

                        //用户积分变化
                        $userUpdata = array();
                        $userUpdata['able_integral'] = $user['able_integral'] - $record['integral_value'];
                        $userUpdata['overtime_integral'] = $user['overtime_integral'] + $record['integral_value'];
                        Db::table("mrs_user")->where('user_id','=',$record['user_id'])->update($userUpdata);
                    }

                    Db::table('mrs_integral_detail')->where('detail_id', '=', $record['detail_id'])->update(array('is_overtime'=>'1'));
                    $overtimeIntegral++;
                }

                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                $overtimeIntegral = 0;
            }
        }
        return 'Auto overtime integral count：'.$overtimeIntegral.'<hr>';
    }

}
