<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/5 0005
 * Time: 20:44
 */

namespace app\api\controller;


use think\Db;
use think\Request;

class Goods extends Base
{

    /**
     * 获取商品列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodslist(Request $request)
    {
        if ($request->isPost()) {
            $page = $request->post('page');
            $sort = $request->post('sort');
            $sort_type = $request->post('sort_type');

            if (empty($page)) {
                $page = 1;
            }
            $pageSize = 10;

            if (empty($sort_type)) {
                $sort_type = 'asc';
            }
            $order = '';
            if (empty($sort) || $sort == 1) { //综合排序
                $order = 'is_top asc,sold_num desc';
            } else if ($sort == 2) { //销量
                $order = 'sold_num desc';
            } else if ($sort == 3) {
                $order = 'goods_price ' . $sort_type;
            }

            $goodsList = Db::table('mrs_goods')
                ->where('goods_status', '=', 1)
                ->field('goods_id,goods_name,goods_img,goods_price')
                ->order($order)
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select();

            $totalCount = Db::table('mrs_goods')
                ->where('goods_status', '=', 1)
                ->count();

            if ($goodsList) {
                $total_page = ceil($totalCount / $pageSize);

                $domain = config('domain');
                foreach ($goodsList as $k => $v) {
                    $goodsList[$k]['goods_img'] = $domain . $v['goods_img'];
                }

                $data['goodslist'] = $goodsList;
                $data['total_page'] = $total_page;
                echo $this->successJson($data);
                exit;
            } else {
                echo $this->errorJson(1, '没有商品信息');
                exit;
            }
        }
    }

    /**
     * 获取商品详情
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodsdetail(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');
            $goods_id = $request->post('goods_id');

            if (empty($goods_id)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            $goods = Db::table('mrs_goods')
                ->where('goods_id', '=', $goods_id)
                ->field('goods_id,goods_name,goods_desc,goods_detail,goods_price')
                ->find();

            if(!empty($user_id)) {
                $cart_count = Db::table('mrs_carts')
                    ->where('user_id', '=', $user_id)
                    ->count();
            }else{
                $cart_count = 0;
            }

            $goodsimgList = Db::table('mrs_goods_img')
                ->where('goods_id', '=', $goods_id)
                ->field('img_path')
                ->order('order_no asc,create_time desc')
                ->select();

            $domain = config('domain');
            foreach ($goodsimgList as $k => $v){
                $goodsimgList[$k]['img_path'] = $domain . $v['img_path'];
            }

            $goods['cart_count'] = $cart_count;
            $goods['goods_img_list'] = $goodsimgList;

            //判断是否已在购物车
            $is_exists = Db::table("mrs_carts")->where(array('user_id' => $user_id, 'goods_id' => $goods_id))->find();
            if(empty($is_exists)){
                $goods['is_incart'] = 0;
            }else{
                $goods['is_incart'] = 1;
            }

            if ($goods) {
                echo $this->successJson($goods);
                exit;
            } else {
                echo $this->errorJson(1, '没有商品信息');
                exit;
            }
        }
    }

}
