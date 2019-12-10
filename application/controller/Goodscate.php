<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:04
 */

namespace app\controller;


use think\Db;
use think\Exception;
use think\Request;

class Goodscate extends Base
{

    /**
     * 商品分类列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $is_actived = $request->param('is_actived');
        $cate_name = $request->param('cate_name');
        $where = [];
        if (!empty($is_actived)) {
            $where[] = ['is_actived', '=', $is_actived];
        }
        if (!empty($cate_name)) {
            $where[] = ['cate_name', '=', $cate_name];
        }

        $goodsCateList = Db::table('mrs_goods_cate')
            ->where($where)
            ->order('order_no asc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $goodsCateList->render();
        $count = Db::table('mrs_goods_cate')->where($where)->count();

        $this->assign('is_actived', $is_actived);
        $this->assign('cate_name', $cate_name);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('goodsCateList', $goodsCateList);
        return $this->fetch();
    }

    /**
     * 添加商品分类
     * @param Request $request
     * @return mixed|void
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data = [];
            $data['cate_name'] = $request->post('cate_name');
            $data['cate_desc'] = $request->post('cate_desc');
            $data['order_no'] = $request->post('order_no');
            $data['is_actived'] = $request->post('is_actived');

            $validate = new \app\validate\GoodsCate();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $data['create_time'] = time();

            $goodsCate = new \app\model\GoodsCate();
            $goodsCate->save($data);
            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }

    /**
     * 修改商品分类
     * @param Request $request
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $data = [];
            $cate_id = $request->post('cate_id');
            $data['cate_name'] = $request->post('cate_name');
            $data['cate_desc'] = $request->post('cate_desc');
            $data['order_no'] = $request->post('order_no');
            $data['is_actived'] = $request->post('is_actived');

            $validate = new \app\validate\GoodsCate();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $goodsCate = new \app\model\GoodsCate();
            $goodsCate->save($data, ['cate_id' => $cate_id]);
            echo $this->successJson();
            return;
        }

        $cate_id = $request->get('cate_id');
        if (empty($cate_id)) {
            $this->errorJson('关键数据错误');
        }
        $goodsCate = \app\model\GoodsCate::where('cate_id', $cate_id)->find();

        $this->assign('goodsCate', $goodsCate);
        return $this->fetch();
    }

    /**
     * 删除商品分类
     * @param Request $request
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $cate_id = $request->post('cate_id');

            if (empty($cate_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            \app\model\GoodsCate::where('cate_id', $cate_id)->delete();
            echo $this->successJson();
            exit;
        }
    }

}
