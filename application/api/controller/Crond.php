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
        $this->autoConfirmOrder();

        //自动取消订单调度
        $this->autoCancelOrder();
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
        echo 'Auto confirm order shipping status count：'.$confirmOrderCount;
        exit;

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

                Db::table('mrs_orders')->where('order_id','in',$orderIdStr)->update($updateData);

                Db::commit();
            }catch (Exception $e){
                Db::rollback();
                $cancelOrderCount = 0;
            }
        }
        echo 'Auto cancel order status count：'.$cancelOrderCount;
        exit;


    }

}
