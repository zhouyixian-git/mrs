<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/21 0021
 * Time: 16:00
 */

namespace app\admin\controller;


use think\Db;
use think\Request;

class Sku extends Base
{

    /**
     * 规格列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $p_sku_id = $request->param('p_sku_id');
        $sku_name = $request->param('sku_name');
        $where = [];
        $where[] = ['is_delete', '=', 2];
        if (!empty($p_sku_id)) {
            $where[] = ['p_sku_id', '=', $p_sku_id];
        }
        if (!empty($sku_name)) {
            $where[] = ['sku_name', '=', $sku_name];
        }

        $skuList = Db::table('mrs_goods_sku')
            ->where($where)
            ->order('order_no asc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $skuList->render();
        $count = Db::table('mrs_goods_sku')->where($where)->count();

        $where = [];
        $where[] = ['p_sku_id', '=', 0];
        $where[] = ['is_delete', '=', 2];
        $pSkuList = Db::table('mrs_goods_sku')->where($where)->order('order_no asc,create_time desc')->select();

        $this->assign('p_sku_id', $p_sku_id);
        $this->assign('sku_name', $sku_name);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('skuList', $skuList);
        $this->assign('pSkuList', $pSkuList);
        return $this->fetch();
    }

    /**
     * 添加商品规格
     * @param Request $request
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data = [];
            $data['sku_name'] = $request->post('sku_name');
            $data['p_sku_id'] = $request->post('p_sku_id');
            $data['p_sku_name'] = $request->post('p_sku_name');
            $data['order_no'] = $request->post('order_no');
            $data['is_actived'] = $request->post('is_actived');

            $validate = new \app\admin\validate\Sku();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            if (empty($data['p_sku_id'])) {
                $data['p_sku_name'] = [];
            }

            $data['create_time'] = time();

            $skuModel = new \app\admin\model\Sku();
            $skuModel->save($data);
            echo $this->successJson();
            return;
        }

        $where = [];
        $where[] = ['p_sku_id', '=', 0];
        $where[] = ['is_delete', '=', 2];
        $pSkuList = Db::table('mrs_goods_sku')->where($where)->order('order_no asc,create_time desc')->select();
        $this->assign('pSkuList', $pSkuList);
        return $this->fetch();
    }

    /**
     * 修改商品规格
     * @param Request $request
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $sku_id = $request->post('sku_id');
            $data['p_sku_id'] = $request->post('p_sku_id');
            $data['p_sku_name'] = $request->post('p_sku_name');
            $data['sku_name'] = $request->post('sku_name');
            $data['order_no'] = $request->post('order_no');
            $data['is_actived'] = $request->post('is_actived');

            $validate = new \app\admin\validate\Sku();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $skuModel = new \app\admin\model\Sku();
            $skuModel->save($data, ['sku_id' => $sku_id]);
            echo $this->successJson();
            return;
        }

        $sku_id = $request->get('sku_id');
        if (empty($sku_id)) {
            $this->error('关键数据错误');
        }
        $sku = \app\admin\model\Sku::where('sku_id', $sku_id)->find();

        $where = [];
        $where[] = ['p_sku_id', '=', 0];
        $where[] = ['is_delete', '=', 2];
        $pSkuList = Db::table('mrs_goods_sku')->where($where)->order('order_no asc,create_time desc')->select();

        $this->assign('pSkuList', $pSkuList);
        $this->assign('sku', $sku);
        return $this->fetch();
    }

    /**
     * 删除商品规格
     * @param Request $request
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $sku_id = $request->post('sku_id');

            if (empty($sku_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            \app\admin\model\Sku::where('sku_id', $sku_id)->update(['is_delete' => 1]);
            echo $this->successJson();
            exit;
        }
    }
}