<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/5 0005
 * Time: 20:12
 */

namespace app\api\controller;


use think\Db;
use think\Request;

class Home extends Base
{
    /**
     * 轮播图获取
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getbanner(Request $request)
    {
        if ($request->isPost()) {
            $type = $request->post('type');
            $type = empty($type)?'1':$type;
            $where = [];
            $where[] = ['is_actived', '=', 1];
            $where[] = ['type', '=', $type];
            $bannerList = Db::table('mrs_home_banner')
                ->where($where)
                ->field('banner_title,link_url,image_url')
                ->order('order_no asc,create_time desc')
                ->select();

            if ($bannerList) {
                $domain = config('domain');
                foreach ($bannerList as $k => $v) {
                    $bannerList[$k]['image_url'] = $domain . $v['image_url'];
                }

                echo $this->successJson($bannerList);
                exit;
            } else {
                echo $this->errorJson(1, '没有轮播图信息');
                exit;
            }
        }
    }

    /**
     * 获取商品分类信息
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodscate(Request $request)
    {
        if ($request->isPost()) {
            $goodscateList = Db::table('mrs_goods_cate')
                ->where('is_actived', '=', 1)
                ->field('cate_name,cate_image,cate_id')
                ->order('order_no asc,create_time desc')
                ->select();

            if ($goodscateList) {
                $domain = config('domain');
                foreach ($goodscateList as $k => $v) {
                    $goodscateList[$k]['cate_image'] = $domain . $v['cate_image'];
                }

                echo $this->successJson($goodscateList);
                exit;
            } else {
                echo $this->errorJson(1, '没有商品分类信息');
                exit;
            }
        }
    }

    /**
     * 获取首页商品列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodslist(Request $request)
    {
        if ($request->isPost()) {
            $goodsList = Db::table('mrs_goods')
                ->where('goods_status', '=', 1)
                ->field('goods_id,goods_name,goods_img,goods_price')
                ->order('is_top asc,create_time desc,sold_num desc')
                ->select();

            if ($goodsList) {
                $domain = config('domain');
                foreach ($goodsList as $k => $v) {
                    $goodsList[$k]['goods_img'] = $domain . $v['goods_img'];
                }

                echo $this->successJson($goodsList);
                exit;
            } else {
                echo $this->errorJson(1, '没有商品信息');
                exit;
            }
        }
    }

}
