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
    }

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
        $where[] = ['shipping_time','<=', time()+86400*$autoConfirmOrderTime];

        $orders = Db::table('mrs_orders')->where($where)->select();
        if(is_array($orders) && count($orders) > 0 ){
            Db::startTrans();
            try{
                $orderArr = array();
                foreach($orders as $k=>$order){
                    $orderArr[] = $order['order_id'];
                    $confirmOrderCount++;
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

}
