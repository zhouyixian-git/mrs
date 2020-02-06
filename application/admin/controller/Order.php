<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/19 0019
 * Time: 9:47
 */

namespace app\admin\controller;


use think\Db;
use think\Request;

class Order extends Base
{

    /**
     * 订单列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $order_sn = $request->param('order_sn');
        $order_status = $request->param('order_status');
        $where = [];
        if (!empty($order_sn)) {
            $where[] = ['t1.order_sn', 'like', "%$order_sn%"];
        }
        if (!empty($order_status)) {
            $where[] = ['t1.order_status', '=', $order_status];
        }

        $domain = config('domain');
        $orderList = Db::table('mrs_orders')
            ->alias('t1')
            ->field('t1.*')
            ->where($where)
            ->order('t1.create_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page'])
            ->each(function ($order, $key) use ($domain) {
                $where = [];
                $where[] = ['order_id', '=', $order['order_id']];
                $goodsList = Db::table('mrs_order_goods')
                    ->field('goods_name,goods_num,goods_m_list_image,goods_price')
                    ->where($where)->select();

                foreach ($goodsList as $k1 => $v1) {
                    $goodsList[$k1]['goods_m_list_image'] = $domain . $v1['goods_m_list_image'];
                }
                $order['goodsList'] = $goodsList;
                return $order;
            });

        $this->assign('order_status', $order_status);
        $this->assign('order_sn', $order_sn);
        $this->assign('orderList', $orderList);
        return $this->fetch();
    }

    /**
     * 订单详情
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view(Request $request)
    {
        $order_id = $request->get('order_id');
        if (empty($order_id)) {
            $this->error('缺少关键参数order_id');
        }

        //订单信息
        $order = Db::table('mrs_orders')->where('order_id', '=', $order_id)->find();
        if (!$order) {
            $this->error('没有找到对应的订单信息');
        }

        //订单商品信息
        $orderGoods = Db::table('mrs_order_goods')->where('order_id', '=', $order_id)->select();
        foreach ($orderGoods as $k => $v){
            $goods_sku = '';
            if(!empty($v['sku_json'])){
                $skuJson = json_decode(json_decode($v['sku_json'], true), true);
                if(!empty($skuJson)) {
                    foreach ($skuJson as $key => $value) {
                        $goods_sku .= $value['sku_name'] . '-';
                    }
                }
                $goods_sku = substr($goods_sku, 0, -1);
            }
            $orderGoods[$k]['goods_sku'] = $goods_sku;
        }

        //订单动作信息
        $orderAction = Db::table('mrs_order_action')->where('order_id', '=', $order_id)->order('create_time desc')->select();

        $order['orderGoods'] = $orderGoods;
        $order['orderAction'] = $orderAction;

        $this->assign('order', $order);
        return $this->fetch();
    }

    /**
     * 订单发货
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function shipping(Request $request)
    {
        if ($request->isPost()) {
            $order_id = $request->post('order_id');
            $data['courier_company'] = $request->post('courier_company');
            $data['courier_number'] = $request->post('courier_number');

            if (empty($order_id)) {
                echo $this->errorJson(0, '缺少关键参数order_id');
                exit;
            }
            if (empty($data['courier_company'])) {
                echo $this->errorJson(0, '请填写快递公司信息');
                exit;
            }
            if (empty($data['courier_number'])) {
                echo $this->errorJson(0, '请填写快递单号信息');
                exit;
            }

            $data['order_status'] = 3;
            $data['shipping_status'] = 2;
            $data['shipping_time'] = time();
            Db::table('mrs_orders')->where('order_id', '=', $order_id)->update($data);

            echo $this->successJson();
            exit;
        }

        $order_id = $request->get('order_id');
        if (empty($order_id)) {
            $this->error('缺少关键参数order_id');
        }

        $this->assign('order_id', $order_id);
        return $this->fetch();
    }

    /**
     * 订单退款
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refund(Request $request)
    {
        if ($request->isPost()) {
            $order_id = $request->post('order_id');
            $refund_status = $request->post('refund_status');
            $refuse_reason = $request->post('refuse_reason');

            if (empty($order_id)) {
                echo $this->errorJson(0, '缺少关键参数order_id');
                exit;
            }

            if($refund_status == 4 && empty($refuse_reason)){
                echo $this->errorJson(0, '请填写拒绝退款理由');
                exit;
            }

            $order = Db::table('mrs_orders')
                ->where('order_id', '=', $order_id)
                ->find();

            if (empty($order)) {
                echo $this->errorJson(0, '没有找到对应的订单信息');
                exit;
            }

            if($refund_status == 4){ //卖家拒绝退款
                $data['refund_status'] = 4;
                $data['refuse_reason'] = $refuse_reason;
                Db::table('mrs_orders')->where('order_id', '=', $order_id)->update($data);

                echo $this->successJson();
                exit;
            }

            //调用微信支付退款接口
            $out_refund_no = 'LZHS' . date('YmdHis', time()) . rand(100000, 999999);
            $wechatModel = new \app\admin\model\Wechat();
            $data['out_trade_no'] = $order['pay_order_sn'];
            $data['out_refund_no'] = $out_refund_no;
            $data['amount'] = $order['cash_amount'];
            $result = $wechatModel->refund($data);
            if ($result['errcode'] == 1) {
                echo $this->errorJson(0, $result['errmsg']);
                exit;
            } else {
                //调用退款查询接口
                $refundResult = $wechatModel->queryRefundOrder($out_refund_no);
                if ($refundResult['errcode'] == 1) { //退款成功
                    //todo 更新订单表信息、订单动作表、订单退款记录表
                    //更新订单表数据
                    Db::startTrans();
                    try {
                        $orderData['order_status'] = 5;
                        $orderData['refund_status'] = 5;
                        $orderData['refund_order_sn'] = $out_refund_no;
                        $orderData['refund_time'] = time();
                        $res1 = Db::table('mrs_orders')
                            ->where('order_id', '=', $order_id)
                            ->update($orderData);

                        //生成订单动作表
                        $actionData['order_id'] = $order_id;
                        $actionData['action_name'] = '管理员退款操作';
                        $actionData['action_user_id'] = parent::$_ADMINID;
                        $actionData['action_user_name'] = parent::$_ADMINNAME;
                        $actionData['action_remark'] = '管理员【' . parent::$_ADMINNAME . '】处理用户退款';
                        $actionData['create_time'] = time();
                        $res2 = Db::table('mrs_order_action')->insert($actionData);

                        //生成退款记录
                        $refundData['order_id'] = $order_id;
                        $refundData['money'] = $order['cash_amount'];
                        $refundData['pay_time'] = time();
                        $refundData['is_pay'] = 1;
                        $refundData['wc_order_id'] = $out_refund_no;
                        $res3 = Db::table('mrs_order_refund_record')->insert($refundData);

                        if ($res1 && $res2 && $res3) {
                            Db::commit();
                            echo $this->successJson();
                            exit;
                        } else {
                            Db::rollback();
                            echo $this->errorJson(0, '退款成功,更新订单数据失败');
                            exit;
                        }

                    } catch (Exception $e) {
                        Db::rollback();
                        echo $this->errorJson(0, '退款成功,更新订单数据异常');
                        exit;
                    }
                }
            }
        }

        $order_id = $request->get('order_id');
        if (empty($order_id)) {
            $this->error('缺少关键参数order_id');
        }

        $this->assign('order_id', $order_id);
        return $this->fetch();
    }

    /**
     * 订单退货
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function sales(Request $request)
    {
        if ($request->isPost()) {
            $order_id = $request->post('order_id');
            $sales_status = $request->post('sales_status');
            $refuse_reason = $request->post('refuse_reason');

            if (empty($order_id)) {
                echo $this->errorJson(0, '缺少关键参数order_id');
                exit;
            }

            if($sales_status == 4 && empty($refuse_reason)){
                echo $this->errorJson(0, '请填写拒绝退货理由');
                exit;
            }

            $order = Db::table('mrs_orders')
                ->where('order_id', '=', $order_id)
                ->find();

            if (empty($order)) {
                echo $this->errorJson(0, '没有找到对应的订单信息');
                exit;
            }

            if($sales_status == 4){ //卖家拒绝退货
                $data['sales_status'] = 4;
                $data['refuse_reason'] = $refuse_reason;
                Db::table('mrs_orders')->where('order_id', '=', $order_id)->update($data);

                echo $this->successJson();
                exit;
            }

            //调用微信支付退款接口
            $out_refund_no = 'LZHS' . date('YmdHis', time()) . rand(100000, 999999);
            $wechatModel = new \app\admin\model\Wechat();
            $data['out_trade_no'] = $order['pay_order_sn'];
            $data['out_refund_no'] = $out_refund_no;
            $data['amount'] = $order['cash_amount'];
            $result = $wechatModel->refund($data);
            if ($result['errcode'] == 1) {
                echo $this->errorJson(0, $result['errmsg']);
                exit;
            } else {
                //调用退款查询接口
                $refundResult = $wechatModel->queryRefundOrder($out_refund_no);
                if ($refundResult['errcode'] == 1) { //退款成功
                    //todo 更新订单表信息、订单动作表、订单退款记录表
                    //更新订单表数据
                    Db::startTrans();
                    try {
                        $orderData['order_status'] = 5;
                        $orderData['sales_status'] = 5;
                        $orderData['refund_order_sn'] = $out_refund_no;
                        $orderData['refund_time'] = time();
                        $res1 = Db::table('mrs_orders')
                            ->where('order_id', '=', $order_id)
                            ->update($orderData);

                        //生成订单动作表
                        $actionData['order_id'] = $order_id;
                        $actionData['action_name'] = '管理员退货操作';
                        $actionData['action_user_id'] = parent::$_ADMINID;
                        $actionData['action_user_name'] = parent::$_ADMINNAME;
                        $actionData['action_remark'] = '管理员【' . parent::$_ADMINNAME . '】处理用户退货';
                        $actionData['create_time'] = time();
                        $res2 = Db::table('mrs_order_action')->insert($actionData);

                        //生成退款记录
                        $refundData['order_id'] = $order_id;
                        $refundData['money'] = $order['cash_amount'];
                        $refundData['pay_time'] = time();
                        $refundData['is_pay'] = 1;
                        $refundData['wc_order_id'] = $out_refund_no;
                        $res3 = Db::table('mrs_order_refund_record')->insert($refundData);

                        if ($res1 && $res2 && $res3) {
                            Db::commit();
                            echo $this->successJson();
                            exit;
                        } else {
                            Db::rollback();
                            echo $this->errorJson(0, '退货成功,更新订单数据失败');
                            exit;
                        }

                    } catch (Exception $e) {
                        Db::rollback();
                        echo $this->errorJson(0, '退货成功,更新订单数据异常');
                        exit;
                    }
                }
            }
        }

        $order_id = $request->get('order_id');
        if (empty($order_id)) {
            $this->error('缺少关键参数order_id');
        }

        $this->assign('order_id', $order_id);
        return $this->fetch();
    }
}
