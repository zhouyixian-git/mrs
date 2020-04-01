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
        recordLog('testContent', 'test.txt');
        echo 'api->index';
//        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">12载初心不改（2006-2018） - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
    }

    /**
     * @param Request $request
     * $user_id 用户ID
     */
    public function getordernum(Request $request)
    {
        try {
            $user_id = $request->post('user_id');

            $where = [];
            if (!empty($user_id)) {
                $where[] = ['user_id', '=', $user_id];
            } else {
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
        } catch (Exception $e) {
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
    public function getorders(Request $request)
    {
        $user_id = $request->post('user_id');
        $page = $request->post('page');
        $query_type = $request->post('query_type');

        $where = [];
        if (!empty($user_id)) {
            $where[] = ['user_id', '=', $user_id];
        } else {
            $result = $this->errorJson(1, '缺少关键参数user_id');
            echo $result;
            exit;
        }
        if (empty($query_type)) {
            $query_type = 1;
        }

        switch ($query_type) {
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

        if (empty($page)) {
            $page = 1;
        }

        $orderList = Db::table('mrs_orders')
            ->field(array('order_id', 'order_sn', 'order_status', 'pay_status', 'shipping_status', 'refund_status', 'sales_status', 'accept_status', 'shipping_amount', 'integral_amount', 'cash_amount', 'create_time'))
            ->where($where)
            ->order('create_time desc')
            ->paginate($this->prepage, false, ['type' => 'page\Page', 'var_page' => 'page', 'page' => $page]);

        $json = json_encode($orderList);
        $result = @json_decode($json, true);

        $orders = $result["data"];
        $orderInfo = array();
        $domain = config('domain');
        if (is_array($orders) && count($orders) > 0) {
            foreach ($orders as $k => $order) {
                $where = [];
                $where[] = ['order_id', '=', $order['order_id']];
                $goodsList = Db::table('mrs_order_goods')
                    ->field('goods_name,goods_num,goods_m_list_image,goods_price,sku_json')
                    ->where($where)->select();

                foreach ($goodsList as $k1 => $v1) {
                    $goodsList[$k1]['goods_m_list_image'] = $domain . $v1['goods_m_list_image'];

                    $goods_sku = '';
                    if (!empty($v1['sku_json'])) {
                        $skuJson = json_decode(json_decode($v1['sku_json'], true), true);
                        if (!empty($skuJson)) {
                            foreach ($skuJson as $key => $value) {
                                $goods_sku .= $value['sku_name'] . '-';
                            }
                        }
                        $goods_sku = substr($goods_sku, 0, -1);
                    }
                    $goodsList[$k1]['goods_sku'] = $goods_sku;
                }

                $orders[$k]['goods'] = $goodsList;

                //创建时间格式化
                $orders[$k]['create_time'] = empty($order['create_time']) ? '' : date('Y-m-d H:i:s', $order['create_time']);
            }
        }
        $data = array();
        $data['total_page'] = $result['last_page'];
        $data['orders'] = $orders;
        $result = $this->successJson($data);

        echo $result;
    }

    public function getorderinfo(Request $request)
    {
        $order_id = intval($request->post('order_id'));

        $where = [];
        if (!empty($order_id)) {
            $where[] = ['order_id', '=', $order_id];
        } else {
            $result = $this->errorJson(1, '缺少关键参数order_id');
            echo $result;
            exit;
        }

        $order = Db::table('mrs_orders')
            ->field(array('order_id', 'order_sn', 'pay_order_sn', 'consignee', 'telephone', 'province_name', 'city_name', 'district_name', 'city_name', 'town_name', 'address', 'courier_company', 'courier_number', 'shipping_time', 'confirm_time', 'order_status', 'pay_status', 'shipping_status', 'refund_status', 'sales_status', 'accept_status', 'shipping_amount', 'integral_amount', 'cash_amount', 'order_amount', 'create_time'))
            ->where(array('order_id' => $order_id))
            ->find();

        if (!empty($order)) {
            $domain = config('domain');
            $where = [];
            $where[] = ['order_id', '=', $order['order_id']];
            $goodsList = Db::table('mrs_order_goods')
                ->field(array('goods_name', 'goods_num', 'goods_m_list_image', 'goods_price', 'sku_json'))
                ->where($where)->select();

            foreach ($goodsList as $k => $v) {
                $goodsList[$k]['goods_m_list_image'] = $domain . $v['goods_m_list_image'];

                $goods_sku = '';
                if (!empty($v['sku_json'])) {
                    $skuJson = json_decode(json_decode($v['sku_json'], true), true);
                    if (!empty($skuJson)) {
                        foreach ($skuJson as $key => $value) {
                            $goods_sku .= $value['sku_name'] . '-';
                        }
                    }
                    $goods_sku = substr($goods_sku, 0, -1);
                }
                $goodsList[$k]['goods_sku'] = $goods_sku;
            }

            $order['goods'] = $goodsList;

            //时间格式化
            $order['create_time'] = empty($order['create_time']) ? '' : date('Y-m-d H:i:s', $order['create_time']);
            $order['shipping_time'] = empty($order['shipping_time']) ? '' : date('Y-m-d H:i:s', $order['shipping_time']);
            $order['confirm_time'] = empty($order['confirm_time']) ? '' : date('Y-m-d H:i:s', $order['confirm_time']);

        } else {
            $result = $this->errorJson(1, '找不到对应的订单信息');
            echo $result;
            exit;
        }

        $data = array();
        $data['order'] = $order;
        $result = $this->successJson($data);
        echo $result;
    }

    public function confirmshipping(Request $request)
    {
        $order_id = intval($request->post('order_id'));

        $order = Db::table('mrs_orders')
            ->where(array('order_id' => $order_id))
            ->find();

        if (empty($order)) {
            $result = $this->errorJson(1, '找不到对应的订单信息');
            echo $result;
            exit;
        }

        if ($order['order_status'] != 3) {
            $result = $this->errorJson(1, '该订单状态无法确认收货');
            echo $result;
            exit;
        }

        $data = array();
        $data['order_status'] = '4';
        $data['shipping_status'] = '3';
        $data['confirm_time'] = time();

        $res = Db::table('mrs_orders')->where('order_id', $order['order_id'])->update($data);
        if ($res) {
            $data = array();

            $result = $this->successJson($data);
            echo $result;
            exit;
        } else {
            $result = $this->errorJson(1, '确认收货失败');
            echo $result;
            exit;
        }
    }

    public function getcrerateorderinfo(Request $request)
    {
        $user_id = intval($request->post('user_id'));
        $cart_ids = $request->post('cart_ids');
        $goods_id = $request->post('goods_id');
        $goods_sku_str = $request->post('goods_sku');
        $detail_id = $request->post('detail_id');

//        $cart_ids = '10,9,8,6';
//        $user_id = '2';
//        $user_id = 1;
//        $goods_id = '33';

        if (empty($cart_ids) && empty($goods_id)) {
            $result = $this->errorJson(1, '缺少关键参数$cart_ids、$goods_id');
            echo $result;
            exit;
        }
        if (!empty($cart_ids)) {
            $cart_ids = explode(',', $cart_ids);
        }
        if (empty($cart_ids) && !empty($goods_id) && empty($goods_sku_str)) {
            $result = $this->errorJson(1, '缺少关键参数goods_sku');
            echo $result;
            exit;
        }
        if (empty($cart_ids) && !empty($goods_id) && empty($goods_sku_str) && empty($detail_id)) {
            $result = $this->errorJson(1, '缺少关键参数detail_id');
            echo $result;
            exit;
        }

        if (empty($user_id)) {
            $result = $this->errorJson(1, '缺少关键参数$user_id');
            echo $result;
            exit;
        }

        $data = array();
        $where = array();
        $where[] = ['user_id', '=', $user_id];
        $address = Db::table('mrs_user_address')
            ->where($where)
            ->field(array('consignee', 'telephone', 'province_name', 'city_name', 'district_name', 'areainfo', 'address'))
            ->find();
        $data['address'] = $address;

        //查询用户积分
        $able_integral = Db::table('mrs_user')->where('user_id', '=', $user_id)->value('able_integral');
        //积分兑换比例
        $integral_rate = Db::table('mrs_system_setting')->where('setting_code', '=', 'integral')->value('setting_value');

        $data['able_integral'] = empty($able_integral) ? 0 : $able_integral;
        $data['integral_rate'] = empty($integral_rate) ? 80 : $integral_rate;

        $data['total_goods_price'] = 0;
        $data['discount_price'] = 0;
        $data['shipping_price'] = 0;
        $data['goodslist'] = array();

        if (is_array($cart_ids) && count($cart_ids) > 0 && !empty($cart_ids)) {
            foreach ($cart_ids as $v) {
                $cart = Db::table('mrs_carts')
                    ->where(array('cart_id' => $v))
                    ->field(array('goods_id', 'goods_name', 'goods_image', 'goods_price', 'goods_num', 'sku_json', 'sku_detail_id as detail_id'))
                    ->find();

                $cart['goods_image'] = SERVER_HOST . $cart['goods_image'];

                $goods_sku = '';
                if (!empty($cart['sku_json'])) {
                    $skuJson = json_decode(json_decode($cart['sku_json'], true), true);
                    if (!empty($skuJson)) {
                        foreach ($skuJson as $key => $value) {
                            $goods_sku .= $value['sku_name'] . '-';
                        }
                    }
                    $goods_sku = substr($goods_sku, 0, -1);
                }
                $cart['goods_sku'] = $goods_sku;
                $data['total_goods_price'] += $cart['goods_price'] * $cart['goods_num'];
                $data['goodslist'][] = $cart;
            }
        } else {
            $goods = Db::table('mrs_goods')->field(array('goods_id', 'goods_name', 'goods_img', 'goods_price'))->where(array('goods_id' => $goods_id))->find();

            $data['total_goods_price'] = $goods['goods_price'];
            $goods['goods_num'] = 1;
            $goods['goods_image'] = SERVER_HOST . $goods['goods_img'];

            $goods_sku = '';
            if (!empty($goods_sku_str)) {
                $skuJson = json_decode($goods_sku_str, true);
                if (!empty($skuJson)) {
                    foreach ($skuJson as $key => $value) {
                        $goods_sku .= $value['sku_name'] . '-';
                    }
                }
                $goods_sku = substr($goods_sku, 0, -1);
            }
            $goods['goods_sku'] = $goods_sku;
            $goods['detail_id'] = $detail_id;
            unset($goods['goods_img']);

            $data['goodslist'][] = $goods;
        }

        $result = $this->successJson($data);
        echo $result;
        exit;

    }

    public function createorder(Request $request)
    {
        $user_id = intval($request->post('user_id'));
        $address_id = intval($request->post('address_id'));
        $cart_ids = $request->post('cart_ids');
        $goods_id = $request->post('goods_id');
        $goods_num = $request->post('goods_num');
        $order_remark = $request->post('order_remark');
        $open_id = $request->post('open_id');
        $goods_sku = $request->post('goods_sku');
        $detail_id = $request->post('detail_id');
        $goods_num = empty($goods_num) ? 1 : $goods_num;
        $pay_type = $request->post('pay_type');
        $integral = $request->post('integral');


        if (empty($cart_ids) && empty($goods_id)) {
            $result = $this->errorJson(1, '缺少关键参数cart_ids、goods_id');
            echo $result;
            exit;
        }
        if (empty($cart_ids) && !empty($goods_id) && empty($goods_sku)) {
            $result = $this->errorJson(1, '缺少关键参数goods_sku');
            echo $result;
            exit;
        }
        if (empty($cart_ids) && !empty($goods_id) && empty($goods_sku) && empty($detail_id)) {
            $result = $this->errorJson(1, '缺少关键参数detail_id');
            echo $result;
            exit;
        }
        if (!empty($cart_ids)) {
            $cart_ids = explode(',', $cart_ids);
        }

        if (empty($user_id)) {
            $result = $this->errorJson(1, '缺少关键参数$user_id');
            echo $result;
            exit;
        }

        if (empty($address_id)) {
            $result = $this->errorJson(1, '缺少关键参数$address_id');
            echo $result;
            exit;
        }
        $address = Db::table('mrs_user_address')
            ->where(array('address_id' => $address_id))
            ->find();

        $user = Db::table('mrs_user')
            ->where(array('user_id' => $user_id))
            ->find();

        $carts = array();
        +$order_amount = 0;
        if (is_array($cart_ids) && count($cart_ids) > 0) {
            foreach ($cart_ids as $v) {
                $cart = Db::table('mrs_carts')
                    ->field(array('goods_id', 'goods_name', 'goods_image', 'goods_price', 'goods_num', 'sku_json', 'sku_detail_id'))
                    ->where(array('cart_id' => $v))
                    ->find();

                $order_amount = bcadd($order_amount, bcmul($cart['goods_price'], $cart["goods_num"], 2), 2);
                $carts[] = $cart;
            }
        } else {
            $goods = Db::table('mrs_goods')
                ->where(array('goods_id' => $goods_id))
                ->find();
            $order_amount = bcmul($goods['goods_price'], $goods_num, 2);
        }

        $integral_amount = 0;
        $integral_rate = 0;
        if ($pay_type == 1) { //微信支付
            //走原来逻辑，不需要修改
        } else if ($pay_type == 2) { //微信支付+积分抵扣
            $where = [];
            $where[] = ['setting_code', '=', 'integral'];
            $integral_rate = Db::table('mrs_system_setting')->where($where)->value('setting_value');
            $integral_rate = empty($integral_rate) ? 80 : $integral_rate;

            $integral_amount = bcmul($integral, $integral_rate * 0.01, 2);
            $order_amount = bcsub($order_amount, $integral_amount, 2);

        } else if ($pay_type == 3) { //积分抵扣
            $where = [];
            $where[] = ['setting_code', '=', 'integral'];
            $integral_rate = Db::table('mrs_system_setting')->where($where)->value('setting_value');
            $integral_rate = empty($integral_rate) ? 80 : $integral_rate;

            $integral_amount = bcmul($integral, $integral_rate * 0.01, 2);
            $order_amount = bcsub($order_amount, 0, 2);
            if ($integral_amount != $order_amount) {
                $result = $this->errorJson(1, '积分抵扣额度与订单额度不相等');
                echo $result;
                exit;
            }
        }


        $order_sn = date('YmdHis', time()) . rand(100000, 999999);
        $pay_order_sn = 'LZHS' . date('YmdHis', time()) . rand(100000, 999999);

        $orderData = array();
        $orderData['order_sn'] = $order_sn;
        $orderData['user_id'] = $user_id;
        $orderData['pay_order_sn'] = $pay_order_sn;
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
        $orderData['integral_amount'] = $integral_amount;
        $orderData['integral_rate'] = $integral_rate;
        $orderData['cash_amount'] = $order_amount;
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
        Db::table('mrs_orders')->insert($orderData);
        $order_id = Db::table('mrs_orders')->getLastInsID();

        $orderGoodsData = array();
        if (is_array($carts) && count($carts)) {
            foreach ($carts as $k => $v) {
                $orderGoodsData[] = [
                    'order_id' => $order_id,
                    'goods_id' => $v['goods_id'],
                    'goods_name' => $v['goods_name'],
                    'goods_num' => $v['goods_num'],
                    'goods_m_list_image' => $v['goods_image'],
                    'user_id' => $user_id,
                    'user_name' => $user['user_name'],
                    'goods_price' => $v['goods_price'],
                    'sku_json' => $v['sku_json'],
                    'sku_detail_id' => $v['sku_detail_id'],
                ];
            }
        } else {
            $orderGoodsData[] = [
                'order_id' => $order_id,
                'goods_id' => $goods['goods_id'],
                'goods_name' => $goods['goods_name'],
                'goods_num' => $goods_num,
                'goods_m_list_image' => $goods['goods_img'],
                'user_id' => $user_id,
                'user_name' => $user['user_name'],
                'goods_price' => $goods['goods_price'],
                'sku_json' => json_encode($goods_sku),
                'sku_detail_id' => $detail_id
            ];
        }
        $res = Db::table('mrs_order_goods')->insertAll($orderGoodsData);

        if ($res) {
            if (is_array($cart_ids) && count($cart_ids) > 0) {
                foreach ($cart_ids as $v) {
//                    echo 'del->'.$v;
                    $where = array();
                    $where[] = ['cart_id', '=', $v];
                    Db::table('mrs_carts')->where($where)->delete();
                }
            }
        }

        if ($pay_type == 2 || $pay_type == 3) { //微信支付+积分 或者 积分抵扣
            Db::table('mrs_user')->where('user_id', '=', $user_id)->setInc('used_integral', $integral);
            Db::table('mrs_user')->where('user_id', '=', $user_id)->setDec('able_integral', $integral);

            //增加对应用户积分明细
            $integralDetail = array();
            $integralDetail['user_id'] = $user_id;
            $integralDetail['integral_value'] = $integral;
            $integralDetail['type'] = 2;
            $integralDetail['action_desc'] = '用户下单使用积分';
            $integralDetail['invalid_time'] = time() + 86400 * 180;
            $integralDetail['create_time'] = time();
            Db::table('mrs_integral_detail')->insert($integralDetail);
        }

        //生成订单动作表
        $actionData['order_id'] = $order_id;
        $actionData['action_name'] = '用户下单';
        $actionData['action_user_id'] = $user_id;
        $actionData['action_user_name'] = $user['user_name'];
        $actionData['action_remark'] = '用户【' . $user['user_name'] . '】下单';
        $actionData['create_time'] = time();
        Db::table('mrs_order_action')->insert($actionData);

        //生成订单支付记录
        $payData['type'] = '1';
        $payData['order_id'] = $order_id;
        $payData['money'] = $order_amount;
        $payData['is_pay'] = 2;
        $payData['user_id'] = $user_id;
        $payData['wc_order_id'] = $pay_order_sn;
        Db::table('mrs_order_pay_record')->insert($payData);

        if($pay_type != 3){

            //获取支付参数
            $domain = config('domain');
            $wechatModel = new \app\api\model\Wechat();
            $data['pay_order_sn'] = $pay_order_sn;
            $data['order_amount'] = $order_amount;   //$order_amount; //todo 默认支付金额设置位0.01，方便测试
            $data['open_id'] = $open_id;
            $data['body'] = '商品购买';
            $data['notify_url'] = $domain . '/api/wechat/paynotice';
            $result = $wechatModel->doPay($data);

            if (isset($result['errcode'])) {
                Db::rollback();
                echo $this->errorJson(1, $result['errmsg']);
                exit;
            }
        }else{
            //积分支付时，修改订单状态
            $time = time();
            $payData['pay_time'] = $time;
            $payData['is_pay'] = 1;
            Db::table('mrs_order_pay_record')->where('wc_order_id', '=', $pay_order_sn)->update($payData);

            //生成订单动作表
            $actionData['order_id'] = $order_id;
            $actionData['action_name'] = '用户下单';
            $actionData['action_user_id'] = $user['user_id'];
            $actionData['action_user_name'] = $user['user_name'];
            $actionData['action_remark'] = '用户【' . $user['user_name'] . '】积分支付订单';
            $actionData['create_time'] = time();
            Db::table('mrs_order_action')->insert($actionData);

            $orderData['order_status'] = 2;
            $orderData['pay_status'] = 2;
            $orderData['shipping_status'] = 1;
            $orderData['refund_status'] = 1;
            $orderData['sales_status'] = 1;
            $orderData['accept_status'] = 0;
            $orderData['accept_status'] = 0;
            $orderData['pay_time'] = $time;
            Db::table('mrs_orders')->where('pay_order_sn', '=', $pay_order_sn)->update($orderData);
        }
        Db::commit();

        $result = $this->successJson($result);
        echo $result;
        exit;
    }

    public function getrefundinfo()
    {
        $where = array();
        $where[] = ['setting_code', '=', 'goods_status_option'];
        $goods_status_option = Db::table('mrs_system_setting')
            ->where($where)
            ->find();
        $goods_status_option = empty($goods_status_option['setting_value']) ? '' : explode('|', $goods_status_option['setting_value']);


        $where = array();
        $where[] = ['setting_code', '=', 'refund_reason_option'];
        $refund_reason_option = Db::table('mrs_system_setting')
            ->where($where)
            ->find();
        $refund_reason_option = empty($refund_reason_option['setting_value']) ? '' : explode('|', $refund_reason_option['setting_value']);

        $data = array();
        $data['goods_status_option'] = $goods_status_option;
        $data['refund_reason_option'] = $refund_reason_option;
        $result = $this->successJson($data);

        echo $result;
        exit;
    }

    public function applyrefund(Request $request)
    {
        $order_id = $request->post('order_id');
        $type = $request->post("type");
        $goods_status = $request->post("goods_status");
        $apply_reason = $request->post("apply_reason");
        $remark = $request->post("remark");

        if (empty($order_id)) {
            $result = $this->errorJson(1, '缺少关键参数order_id');
            echo $result;
            exit;
        }
        $order = Db::table('mrs_orders')
            ->where(array('order_id' => $order_id))
            ->find();
        if (empty($order)) {
            $result = $this->errorJson(1, '没有找到对应的订单信息');
            echo $result;
            exit;
        }

        $type = empty($type) ? "1" : $type;

        $data = array();
        if ($type == 1) {
            $data['refund_status'] = '2';
        } else {
            $data['sales_status'] = '2';
        }
        $data['refund_reason'] = $apply_reason;
        $data['refund_goods_status'] = $goods_status;
        $data['refund_desc'] = $remark;

        $res = Db::table('mrs_orders')->where('order_id', $order['order_id'])->update($data);

        if ($res) {
            $result = $this->successJson(array());
            echo $result;
            exit;
        } else {
            echo $this->errorJson(1, '退款失败');
            exit;
        }
    }

    /**
     * 发起支付
     * @param Request $request
     */
    public function dopay(Request $request)
    {
        $order_id = $request->post('order_id');

        if (empty($order_id)) {
            echo $this->errorJson(1, '缺少关键参数order_id');
            exit;
        }

        $order = Db::table('mrs_orders')
            ->alias('t1')
            ->field('t1.pay_order_sn,t1.order_amount,t2.open_id')
            ->leftJoin('mrs_user t2', 't1.user_id = t2.user_id')
            ->where('t1.order_id', '=', $order_id)
            ->find();
        if (empty($order)) {
            $result = $this->errorJson(1, '没有找到对应的订单信息');
            echo $result;
            exit;
        }

        //获取支付参数
        $domain = config('domain');
        $wechatModel = new \app\api\model\Wechat();
        $data['pay_order_sn'] = $order['pay_order_sn'];
        $data['order_amount'] = $order['order_amount'];
        $data['open_id'] = $order['open_id'];
        $data['body'] = '商品购买';
        $data['notify_url'] = $domain . '/api/wechat/paynotice';
        $result = $wechatModel->doPay($data);

        echo $this->successJson($result);
        exit;
    }

    public function ordercancel(Request $request)
    {
        $order_id = $request->post('order_id');
        if (empty($order_id)) {
            echo $this->errorJson('1', '缺少关键参数order_id');
            exit;
        }

        $order = Db::table('mrs_orders')->where('order_id', '=', $order_id)->find();

        if (empty($order)) {
            echo $this->errorJson('1', '订单不存在');
            exit;
        }
        if ($order['order_status'] == '5') {
            echo $this->errorJson('1', '该订单状态已取消成功');
            exit;
        }
        if ($order['order_status'] != '1') {
            echo $this->errorJson('1', '该订单状态不能取消');
            exit;
        }
        if ($order['pay_status'] != '1') {
            echo $this->errorJson('1', '该订单已付款，取消失败');
            exit;
        }

        $orderArr[] = $order['order_id'];

        Db::startTrans();
        try {
            //生成订单动作表
            $actionData['order_id'] = $order['order_id'];
            $actionData['action_name'] = '用户取消订单';
            $actionData['action_user_id'] = $order['user_id'];
            $actionData['action_user_name'] = $order['user_name'];
            $actionData['action_remark'] = '用户取消订单';
            $actionData['create_time'] = time();
            Db::table('mrs_order_action')->insert($actionData);

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

            Db::table('mrs_orders')->where('order_id', '=', $order_id)->update($updateData);
            Db::commit();
            echo $this->successJson();
            exit;
        } catch (Exception $e) {
            echo $this->errorJson('1', '取消订单异常');
            exit;
            Db::rollback();
        }
    }

}
