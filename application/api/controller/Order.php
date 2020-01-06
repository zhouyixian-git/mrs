<?php
namespace app\api\controller;

use think\Db;
use think\Request;
use think\Exception;

class Order extends Base
{
    public $prepage = 5;

    public function index()
    {
        echo 'api->index';
//        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">12载初心不改（2006-2018） - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
    }

    public function getordernum(){
        try{
            $user_id = $this->request->param('user_id');

            $where = [];
            if (!empty($user_id)) {
                $where[] = ['user_id', '=', $user_id];
            }else{
                $result = $this->errorJson(1, '功能异常，请稍后重试');
                echo $result;
                exit;
            }

            //total
            $total = Db::table('mrs_orders')->where($where)->count();

            //unpay
            $where1 = $where;
            $where1[] = ['order_status', '=', '1'];
            $unpay = Db::table('mrs_orders')->where($where1)->count();


            //unshipping
            $where2 = $where;
            $where2[] = ['order_status', '=', '2'];
            $unshipping = Db::table('mrs_orders')->where($where2)->count();

            //finish
            $where3 = $where;
            $where3[] = ['order_status', '=', '4'];
            $finish = Db::table('mrs_orders')->where($where3)->count();

            //refund
            $where4 = $where;
            $where4[] = ['refund_status', '<>', '1'];
            $refund = Db::table('mrs_orders')->where($where4)->count();

            //sales_
            $where5 = $where;
            $where5[] = ['sales_status', '<>', '1'];
            $sales = Db::table('mrs_orders')->where($where5)->count();

            $data = array();
            $data['total'] = $total;
            $data['unpay'] = $unpay;
            $data['unshipping'] = $unshipping;
            $data['finish'] = $finish;
            $data['refund'] = intval($refund + $sales);

            $result = $this->successJson($data);

            echo $result;
        }catch(Exception $e){
            $result = $this->errorJson(1, '功能异常，请稍后重试');
            echo $result;
            exit;
        }
    }


    public function getorders(){
        try{
            $user_id = $this->request->param('user_id');
            $page = $this->request->param('page');
            $query_type = $this->request->param('query_type');

            $where = [];
            if (!empty($user_id)) {
                $where[] = ['user_id', '=', $user_id];
            }
            else{
                $result = $this->errorJson(1, '缺少关键参数user_id');
                echo $result;
                exit;
            }
            if(empty($query_type)){
                $query_type = 1;
            }

            if(empty($page)){
                $page = 1;
            }


            $orderList = Db::table('mrs_orders')
                ->where($where)
                ->order('create_time desc')
                ->paginate($this->prepage, false, ['type' => 'page\Page', 'var_page' => 'page','page' => $page]);

//            $orders = $orderList
            $json = json_encode($orderList);
            $result = @json_decode($json, true);

            $orders = $result["data"];
            $data = array();
            $data['total_page'] = $result['total'];
            $data['orders'] = $result['orders'];
            $result = $this->successJson($data);

            echo $result;
        }catch(Exception $e){
            $this->errorJson(1, '功能异常，请稍后重试');
        }
    }
}
