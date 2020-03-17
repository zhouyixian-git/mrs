<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/9 0009
 * Time: 22:58
 */

namespace app\admin\controller;


use think\Db;
use think\Request;

class Region extends Base
{

    /**
     * 区域配置
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $region_name = $request->param('region_name');
        $where = [];
        if (!empty($region_name)) {
            $where[] = ['region_name', '=', $region_name];
        }

        $regionList = Db::table('mrs_region')
            ->where($where)
            ->order('create_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $regionList->render();
        $count = Db::table('mrs_region')->where($where)->count();

        $this->assign('region_name', $region_name);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('regionList', $regionList);
        return $this->fetch();
    }

    /**
     * 添加区域
     * @param Request $request
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data = [];
            $data['region_name'] = $request->post('region_name');
            $data['remark'] = $request->post('remark');

            $validate = new \app\admin\validate\Region();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $data['create_time'] = time();

            $region = new \app\admin\model\Region();
            $region->save($data);
            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }

    /**
     * 修改区域
     * @param Request $request
     */
    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $data = [];
            $region_id = $request->post('region_id');
            $data['region_name'] = $request->post('region_name');
            $data['remark'] = $request->post('remark');

            $validate = new \app\admin\validate\Region();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $region = new \app\admin\model\Region();
            $region->save($data, ['region_id' => $region_id]);
            echo $this->successJson();
            return;
        }

        $region_id = $request->get('region_id');
        if (empty($region_id)) {
            $this->errorJson('关键数据错误');
        }
        $region = \app\admin\model\Region::where('region_id', $region_id)->find();

        $this->assign('region', $region);
        return $this->fetch();
    }

    /**
     * 删除区域
     * @param Request $request
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $region_id = $request->post('region_id');

            if (empty($region_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            $count = Db::table('mrs_site')->where('region_id', '=', $region_id)->count();
            if ($count > 0) {
                echo $this->errorJson(0, '该区域已经被使用，不可删除');
                exit;
            }

            \app\admin\model\Region::where('region_id', $region_id)->delete();
            echo $this->successJson();
            exit;
        }
    }
}
