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
            $data['cate_id'] = $request->post('cate_id');
            $data['cate_name'] = $request->post('cate_name');
            $data['goods_name'] = $request->post('goods_name');
            $data['goods_desc'] = $request->post('goods_desc');
            $data['goods_detail'] = $request->post('goods_detail');
            $data['goods_price'] = $request->post('goods_price');
            $data['is_top'] = $request->post('is_top');
            $data['is_cash'] = $request->post('is_cash');
            $data['max_cash'] = $request->post('max_cash');
            $data['goods_status'] = $request->post('goods_status');

            $validate = new \app\validate\Goods();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            //$data['goods_img'] = $request->post('');
            $data['create_time'] = time();
            $data['cteate_admin_id'] = parent::$loginAdmin['admin_id'];

            Db::startTrans();
            try {
                Db::table('mrs_goods')->insert($data);
                //Db::table('eas_role_menu')->insertAll($menuList);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                echo $this->errorJson(0, $e->getMessage());
                return;
            }

            echo $this->successJson();
            return;
        }

        $goodsCateList = \app\model\GoodsCate::where('is_actived', 1)->order('order_no asc,create_time desc')->select();
        $this->assign('goodsCateList', $goodsCateList);
        return $this->fetch();
    }

    /**
     * 编辑商品
     * @param Request $request
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $goods_id = $request->post('goods_id');
            $data['cate_id'] = $request->post('cate_id');
            $data['cate_name'] = $request->post('cate_name');
            $data['goods_name'] = $request->post('goods_name');
            $data['goods_desc'] = $request->post('goods_desc');
            $data['goods_detail'] = $request->post('goods_detail');
            $data['goods_price'] = $request->post('goods_price');
            $data['is_top'] = $request->post('is_top');
            $data['is_cash'] = $request->post('is_cash');
            $data['max_cash'] = $request->post('max_cash');
            $data['goods_status'] = $request->post('goods_status');

            $validate = new \app\validate\Goods();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            //$data['goods_img'] = $request->post('');

            Db::startTrans();
            try {
                Db::table('mrs_goods')->where('goods_id',$goods_id)->update($data);
                //Db::table('eas_role_menu')->insertAll($menuList);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                echo $this->errorJson(0, $e->getMessage());
                return;
            }

            echo $this->successJson();
            return;
        }

        $goods_id = $request->get('goods_id');
        if (empty($goods_id)) {
            $this->error('关键数据错误');
        }
        $goods = \app\model\Goods::where('goods_id', $goods_id)->find();

        $goodsCateList = \app\model\GoodsCate::where('is_actived', 1)->order('order_no asc,create_time desc')->select();
        $this->assign('goods', $goods);
        $this->assign('goodsCateList', $goodsCateList);
        return $this->fetch();
    }

    /**
     * 删除商品
     * @param Request $request
     */
    public function delete(Request $request){
        if ($request->isPost()) {
            $goods_id = $request->post('goods_id');

            if (empty($goods_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            Db::startTrans();
            try {
                Db::table('mrs_goods_img')->where(['goods_id' => $goods_id])->delete();
                Db::table('mrs_goods')->where(['goods_id' => $goods_id])->delete();
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }
            echo $this->successJson();
            exit;
        }
    }

}
