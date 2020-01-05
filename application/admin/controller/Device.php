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

class Device extends Base
{

    /**
     * 设备列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $status = $request->param('status');
        $site_id = $request->param('site_id');
        $device_name = $request->param('device_name');
        $where = [];
        if (!empty($status)) {
            $where[] = ['t1.status', '=', $status];
        }
        if (!empty($device_name)) {
            $where[] = ['t1.device_name', '=', $device_name];
        }
        if (!empty($site_id)) {
            $where[] = ['t1.site_id', '=', $site_id];
        }

        $deviceList = Db::table('mrs_device')
            ->field('t1.*,t2.site_name')
            ->alias('t1')
            ->leftJoin('mrs_site t2', 't1.site_id = t2.site_id')
            ->where($where)
            ->order('t1.create_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $deviceList->render();
        $count = Db::table('mrs_device')->alias('t1')->where($where)->count();

        $siteList = Db::table('mrs_site')->where([['status', '=', 1], ['is_delete', '=', '2']])->order('create_time desc')->select();

        $this->assign('status', $status);
        $this->assign('site_id', $site_id);
        $this->assign('device_name', $device_name);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('deviceList', $deviceList);
        $this->assign('siteList', $siteList);
        return $this->fetch();
    }

    /**
     * 新增设备
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data['device_name'] = $request->post('device_name');
            $data['device_code'] = $request->post('device_code');
            $data['device_desc'] = $request->post('device_desc');
            $data['site_id'] = $request->post('site_id');
            $data['status'] = $request->post('status');
            $data['lng'] = $request->post('lng');
            $data['lat'] = $request->post('lat');

            $validate = new \app\admin\validate\Device();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $data['create_time'] = time();

            $device = new \app\admin\model\Device();
            $device->save($data);
            echo $this->successJson();
            return;
        }

        $siteList = Db::table('mrs_site')->where([['status', '=', 1], ['is_delete', '=', '2']])->order('create_time desc')->select();
        $this->assign('siteList', $siteList);
        return $this->fetch();
    }

    /**
     * 修改设备
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $device_id = $request->post('device_id');
            $data['device_name'] = $request->post('device_name');
            $data['device_code'] = $request->post('device_code');
            $data['device_desc'] = $request->post('device_desc');
            $data['site_id'] = $request->post('site_id');
            $data['status'] = $request->post('status');
            $data['lng'] = $request->post('lng');
            $data['lat'] = $request->post('lat');

            $validate = new \app\admin\validate\Device();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $device = new \app\admin\model\Device();
            $device->save($data, ['device_id' => $device_id]);
            echo $this->successJson();
            return;
        }

        $device_id = $request->get('device_id');
        if (empty($device_id)) {
            $this->errorJson('关键数据错误');
        }
        $device = \app\admin\model\Device::where('device_id', $device_id)->find();

        $this->assign('device', $device);

        $siteList = Db::table('mrs_site')->where([['status', '=', 1], ['is_delete', '=', '2']])->order('create_time desc')->select();
        $this->assign('siteList', $siteList);
        return $this->fetch();
    }

    /**
     * 删除设备
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $device_id = $request->post('device_id');

            if (empty($device_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            \app\admin\model\Device::where('device_id', $device_id)->delete();
            echo $this->successJson();
            exit;
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
