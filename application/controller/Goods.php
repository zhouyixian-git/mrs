<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/10 0010
 * Time: 22:23
 */

namespace app\controller;


use think\Db;
use think\Request;

class Goods extends Base
{

    /**
     * 商品列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $goods_name = $request->param('goods_name');
        $cate_id = $request->param('cate_id');
        $where = [];
        if (!empty($goods_name)) {
            $where[] = ['goods_name', 'like', "%$goods_name%"];
        }
        if (!empty($cate_id)) {
            $where[] = ['cate_id', '=', $cate_id];
        }

        $goodsList = Db::table('mrs_goods')
            ->where($where)
            ->order('create_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $goodsList->render();
        $count = Db::table('mrs_goods')->where($where)->count();

        $goodsCateList = \app\model\GoodsCate::where('is_actived', 1)->order('order_no asc,create_time desc')->select();
        $this->assign('goodsCateList', $goodsCateList);
        $this->assign('cate_id', $cate_id);
        $this->assign('goods_name', $goods_name);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('goodsList', $goodsList);
        return $this->fetch();
    }

    /**
     * 添加商品
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {

        }

        $goodsCateList = \app\model\GoodsCate::where('is_actived', 1)->order('order_no asc,create_time desc')->select();
        $this->assign('goodsCateList', $goodsCateList);
        return $this->fetch();
    }

}
