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

}