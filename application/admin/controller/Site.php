<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/15 0015
 * Time: 12:44
 */

namespace app\admin\controller;


use think\Db;
use think\Request;

class Site extends Base
{

    /**
     * 站点列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $status = $request->param('status');
        $site_name = $request->param('site_name');
        $where = [];
        $where[] = ['is_delete', '=', 2];
        if (!empty($status)) {
            $where[] = ['status', '=', $status];
        }
        if (!empty($site_name)) {
            $where[] = ['site_name', '=', $site_name];
        }

        $siteList = Db::table('mrs_site')
            ->where($where)
            ->order('create_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $siteList->render();
        $count = Db::table('mrs_site')->where($where)->count();

        $this->assign('status', $status);
        $this->assign('site_name', $site_name);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('siteList', $siteList);
        return $this->fetch();
    }

    /**
     * 添加站点
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data['site_name'] = $request->post('site_name');
            $data['lng'] = $request->post('lng');
            $data['lat'] = $request->post('lat');
            $data['site_address'] = $request->post('site_address');
            $data['province_id'] = $request->post('province_id');
            $data['city_id'] = $request->post('city_id');
            $data['area_id'] = $request->post('area_id');
            $data['province_name'] = $request->post('province_name');
            $data['city_name'] = $request->post('city_name');
            $data['area_name'] = $request->post('area_name');
            $data['status'] = $request->post('status');

            $validate = new \app\admin\validate\Site();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $data['create_time'] = time();

            $site = new \app\admin\model\Site();
            $site->save($data);
            echo $this->successJson();
            return;
        }

        $provinceList = \app\admin\model\Area::where('parent_id', '=', 0)->select();
        $this->assign('provinceList', $provinceList);
        return $this->fetch();
    }

    /**
     * 修改站点
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $site_id = $request->post('site_id');
            $data['site_name'] = $request->post('site_name');
            $data['lng'] = $request->post('lng');
            $data['lat'] = $request->post('lat');
            $data['site_address'] = $request->post('site_address');
            $data['province_id'] = $request->post('province_id');
            $data['city_id'] = $request->post('city_id');
            $data['area_id'] = $request->post('area_id');
            $data['province_name'] = $request->post('province_name');
            $data['city_name'] = $request->post('city_name');
            $data['area_name'] = $request->post('area_name');
            $data['status'] = $request->post('status');

            $validate = new \app\admin\validate\Site();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $site = new \app\admin\model\Site();
            $site->save($data, ['site_id' => $site_id]);
            echo $this->successJson();
            return;
        }

        $site_id = $request->get('site_id');
        if (empty($site_id)) {
            $this->errorJson('关键数据错误');
        }
        $site = \app\admin\model\Site::where('site_id', $site_id)->find();

        $this->assign('site', $site);

        $provinceList = \app\admin\model\Area::where('parent_id', '=', 0)->select();
        $this->assign('provinceList', $provinceList);
        return $this->fetch();
    }

    /**
     * 删除站点
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $site_id = $request->post('site_id');

            if (empty($site_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            $site = new \app\admin\model\Site();
            $site->save(['is_delete' => 1], ['site_id' => $site_id]);
            echo $this->successJson();
            exit;
        }
    }

    /**
     * 获取地区信息
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getarea(Request $request)
    {
        if ($request->isPost()) {
            $parent_id = $request->post('parent_id');

            if (empty($parent_id)) {
                echo $this->errorJson(0, '缺少关键参数');
                return;
            }

            $areaList = \app\admin\model\Area::where('parent_id', '=', $parent_id)->select();

            echo $this->successJson($areaList);
            return;
        }
    }

    /**
     * 跳转地图
     * @param Request $request
     * @return mixed
     */
    public function map(Request $request)
    {

        $lng = $request->get('lng');
        $lat = $request->get('lat');
        $keyword = $request->get('keyword');

        $this->assign('lng', $lng);
        $this->assign('lat', $lat);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }

}
