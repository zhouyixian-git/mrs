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
        recordLog('testContent','test.txt');
        echo 'api->index';
//        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">12载初心不改（2006-2018） - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
    }

    /**
     * @param Request $request
     * $user_id 用户ID
     */
    public function getordernum(Request $request){
        try{
            $user_id = $request->post('user_id');

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

            //sales
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


    /**
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * $user_id 用户ID
     * $page 所查询的当前页
     * $query_type 查询类型（1:所有订单；2：待付款；3：待收货；4：已完成；5:退款/售后订单）
     */
    public function getorders(Request $request){
            $user_id = $request->post('user_id');
            $page = $request->post('page');
            $query_type = $request->post('query_type');

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

            switch ($query_type){
                case '2':
                    $where[] = ['order_status', '=', 1];
                    break;
                case '3':
                    $where[] = ['order_status', 'in', '2,3'];
                    break;
                case '4':
                    $where[] = ['order_status', '=', 4];
                    break;
                case '5':
                    $where[] = ['refund_status*sales_status', '>', '1'];
                    break;
            }

            if(empty($page)){
                $page = 1;
            }

            $orderList = Db::table('mrs_orders')
                ->field(array('order_id','order_sn','order_status','pay_status','shipping_status','refund_status','sales_status','accept_status','shipping_amount','integral_amount','cash_amount','create_time'))
                ->where($where)
                ->order('create_time desc')
                ->paginate($this->prepage, false, ['type' => 'page\Page', 'var_page' => 'page','page' => $page])
            ;

            $json = json_encode($orderList);
            $result = @json_decode($json, true);

            $orders = $result["data"];
            $orderInfo = array();
            if(is_array($orders) && count($orders) > 0){
                foreach ($orders as $k=>$order){
                    $where = [];
                    $where[] = ['order_id', '=', $order['order_id']];
                    $goodsList = Db::table('mrs_order_goods')
                        ->field(array('goods_name','goods_num','goods_m_list_image','goods_price'))
                        ->where($where)->select();
                    $orders[$k]['goods'] = $goodsList;

                    //创建时间格式化
                    $orders[$k]['create_time'] = empty($order['create_time'])?'':date('Y-m-d H:i:s', $order['create_time']);
                }
            }
            $data = array();
            $data['total_page'] = $result['total'];
            $data['orders'] = $orders;
            $result = $this->successJson($data);

            echo $result;
    }

    public function getorderinfo(Request $request){
        $order_id = intval($request->post('order_id'));

        $where = [];
        if (!empty($order_id)) {
            $where[] = ['order_id', '=', $order_id];
        }
        else{
            $result = $this->errorJson(1, '缺少关键参数order_id');
            echo $result;
            exit;
        }

        $order = Db::table('mrs_orders')
            ->field(array('order_id','order_sn','pay_order_sn','consignee','telephone','province_name','city_name','district_name','city_name','town_name','address','courier_company','courier_number','shipping_time','confirm_time','order_status','pay_status','shipping_status','refund_status','sales_status','accept_status','shipping_amount','integral_amount','cash_amount','create_time'))
            ->find($order_id);

        if(!empty($order)){
            $where = [];
            $where[] = ['order_id', '=', $order['order_id']];
            $goodsList = Db::table('mrs_order_goods')
                ->field(array('goods_name','goods_num','goods_m_list_image','goods_price'))
                ->where($where)->select();
            $order['goods'] = $goodsList;

            //时间格式化
            $order['create_time'] = empty($order['create_time'])?'':date('Y-m-d H:i:s', $order['create_time']);
            $order['shipping_time'] = empty($order['shipping_time'])?'':date('Y-m-d H:i:s', $order['shipping_time']);
            $order['confirm_time'] = empty($order['confirm_time'])?'':date('Y-m-d H:i:s', $order['confirm_time']);

        }else{
            $result = $this->errorJson(1, '找不到对应的订单信息');
            echo $result;
            exit;
        }

        $data = array();
        $data['order'] = $order;
        $result = $this->successJson($data);
        echo $result;
    }

    public function confirmshipping(Request $request){
        $order_id = intval($request->post('order_id'));

        $order = Db::table('mrs_orders')
            ->find($order_id);

        if(empty($order)){
            $result = $this->errorJson(1, '找不到对应的订单信息');
            echo $result;
            exit;
        }

        if($order['order_status'] != 3){
            $result = $this->errorJson(1, '该订单状态无法确认收货');
            echo $result;
            exit;
        }

        $data = array();
        $data['order_status'] = '4';
        $data['confirm_time'] = time();

        $res = Db::table('mrs_orders')->where('order_id', $order['order_id'])->update($data);
        if($res){
            $data = array();

            $result = $this->successJson($data);
            echo $result;
            exit;
        }else{
            $result = $this->errorJson(1, '确认收货失败');
            echo $result;
            exit;
        }
    }

    public function getcrerateorderinfo(Request $request){
        $user_id = intval($request->post('user_id'));
        $cart_ids = $request->post('cart_ids');

        if(empty($cart_ids)){
            $result = $this->errorJson(1, '缺少关键参数$cart_ids');
            echo $result;
            exit;
        }
        $cart_ids = explode(',',$cart_ids);

        if(empty($user_id)){
            $result = $this->errorJson(1, '缺少关键参数$user_id');
            echo $result;
            exit;
        }

        $data = array();
        $where = array();
        $where[] = ['user_id', '=', $user_id];
        $address = Db::table('mrs_user_address')
            ->where($where)
            ->field(array('consignee','telephone','province_name','city_name','district_name','address'))
            ->find();
        $data['address'] = $address;

        $data['total_goods_price'] = 0;
        $data['discount_price'] = 0;
        $data['shipping_price'] = 0;
        $data['goodslist'] = array();

        if(is_array($cart_ids) && count($cart_ids) > 0){
            foreach ($cart_ids as $v){
                $cart = Db::table('mrs_carts')
                    ->field(array('goods_name','goods_image','goods_price','goods_num'))
                    ->find($v);

                $data['discount_price'] += $cart['goods_price'] * $cart['goods_num'];
                $data['goodslist'][] = $cart;
            }
        }

        $result = $this->successJson($data);
        echo $result;
        exit;

    }

    public function createorder(Request $request){
        $user_id = intval($request->post('user_id'));
        $address_id = intval($request->post('address_id'));
        $cart_ids = $request->post('cart_ids');
        $order_remark = $request->post('order_remark');

        $address_id= '1';
        $cart_ids = '1';
        $user_id = '1';
        $order_remark = '测试造数据';

        if(empty($cart_ids)){
            $result = $this->errorJson(1, '缺少关键参数$cart_ids');
            echo $result;
            exit;
        }
        $cart_ids = explode(',',$cart_ids);

        if(empty($user_id)){
            $result = $this->errorJson(1, '缺少关键参数$user_id');
            echo $result;
            exit;
        }

        if(empty($address_id)){
            $result = $this->errorJson(1, '缺少关键参数$address_id');
            echo $result;
            exit;
        }
        $address = Db::table('mrs_user_address')
            ->find($address_id);

        $user = Db::table('mrs_user')
            ->find($user_id);

        $carts = array();
        $order_amount = 0;
        if(is_array($cart_ids) && count($cart_ids) > 0){
            foreach ($cart_ids as $v){
                $cart = Db::table('mrs_carts')
                    ->field(array('goods_id','goods_name','goods_image','goods_price','goods_num'))
                    ->find($v);

                $order_amount = $cart['goods_price'] * $cart["goods_num"];
                $carts[] = $cart;
            }
        }

        $order_sn = date('YmdHis', time()).rand(100000,999999);

        $orderData = array();
        $orderData['order_sn'] = $order_sn;
        $orderData['user_id'] = $user_id;
        $orderData['pay_order_sn'] = '';
        $orderData['user_name'] = $user['user_name'];
        $orderData['order_status'] = '1';
        $orderData['pay_status'] = '1';
        $orderData['shipping_status'] = '1';
        $orderData['refund_status'] = '1';
        $orderData['sales_status'] = '1';
        $orderData['accept_status'] = '1';
        $orderData['pay_type'] = '1';
        $orderData['order_amount'] = $order_amount;
        $orderData['shipping_amount'] = 0;
        $orderData['integral_amount'] = $order_amount;
        $orderData['cash_amount'] = 0;
        $orderData['consignee'] = $address['consignee'];
        $orderData['telephone'] = $address['telephone'];
        $orderData['province_name'] = $address['province_name'];
        $orderData['city_name'] = $address['city_name'];
        $orderData['district_name'] = $address['district_name'];
//        $orderData['town_name'] = $address['town_name'];
        $orderData['address'] = $address['address'];
        $orderData['courier_company'] = '';
        $orderData['courier_number'] = '';
        $orderData['create_time'] = time();
        $orderData['order_remark'] = $order_remark;

        Db::startTrans();
        $order_id = Db::table('mrs_orders')->insert($orderData);

        $orderGoodsData = array();
        if(is_array($carts) && count($carts)){
            foreach ($carts as $k=>$v){
                $orderGoodsData[] = [
                    'order_id' => $order_id,
                    'goods_id' => $v['goods_id'],
                    'goods_name' => $v['goods_name'],
                    'goods_num' => $v['goods_num'],
                    'goods_m_list_image' => $v['goods_image'],
                    'user_id' => $user_id,
                    'user_name'=>$user['user_name'],
                    'goods_price' => $v['goods_price']
                ];
            }
        }
        $res = Db::table('mrs_order_goods')->insertAll($orderGoodsData);

        if($res){
            if(is_array($cart_ids) && count($cart_ids) > 0) {
                foreach ($cart_ids as $v) {
//                    echo 'del->'.$v;
                    $where = array();
                    $where[] = ['cart_id' , '=', $v];
                    Db::table('mrs_carts')->where($where)->delete();
                }
            }
        }
        Db::commit();

        $result = $this->successJson();
        echo $result;
        exit;
    }

    public function getrefundinfo(){
        $where = array();
        $where[] = ['setting_code', '=', 'goods_status_option'];
        $goods_status_option = Db::table('mrs_system_setting')
            ->where($where)
            ->find();
        $goods_status_option = empty($goods_status_option['setting_value'])?'':explode('|', $goods_status_option['setting_value']);


        $where = array();
        $where[] = ['setting_code', '=', 'refund_reason_option'];
        $refund_reason_option = Db::table('mrs_system_setting')
            ->where($where)
            ->find();
        $refund_reason_option = empty($refund_reason_option['setting_value'])?'':explode('|', $refund_reason_option['setting_value']);

        $data = array();
        $data['goods_status_option'] = $goods_status_option;
        $data['refund_reason_option'] = $refund_reason_option;
        $result = $this->successJson($data);

        echo $result;
        exit;
    }

    public function applyrefund(Request $request){
        $order_id = $request->post('order_id');
        $type = $request->post("type");
        $goods_status = $request->post("goods_status");
        $apply_reason = $request->post("apply_reason");
        $order_id = '1';
        $goods_status = '未到货';
        $apply_reason = '不喜欢';

        if(empty($order_id)){
            $result = $this->errorJson(1, '缺少关键参数$order_id');
            echo $result;
            exit;
        }
        $order = Db::table('mrs_orders')
        ->find($order_id);
        if(empty($order)){
            $result = $this->errorJson(1, '没有找到对应的订单信息');
            echo $result;
            exit;
        }

        $type = empty($type)?"1":$type;

        $data = array();
        if($type == 1){
            $data['refund_status'] = '2';
        }else{
            $data['sales_status'] = '2';
        }
        $data['refund_reason'] = $apply_reason;
        $data['refund_goods_status'] = $goods_status;

        $res = Db::table('mrs_orders')->where('order_id', $order['order_id'])->update($data);


        $result = $this->successJson(array());
        echo $result;
        exit;
    }
}
