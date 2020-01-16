<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:04
 */

namespace app\admin\controller;


use think\Db;
use think\Exception;
use think\Request;

class Banner extends Base
{

    /**
     * banner列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $is_actived = $request->param('is_actived');
        $banner_title = $request->param('banner_title');
        $where = [];
        if (!empty($is_actived)) {
            $where[] = ['is_actived', '=', $is_actived];
        }
        if (!empty($banner_title)) {
            $where[] = ['banner_title', '=', $banner_title];
        }

        $bannerList = Db::table('mrs_home_banner')
            ->where($where)
            ->order('order_no asc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $bannerList->render();
        $count = Db::table('mrs_home_banner')->where($where)->count();

        $this->assign('is_actived', $is_actived);
        $this->assign('banner_title', $banner_title);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('bannerList', $bannerList);
        return $this->fetch();
    }

    /**
     * 添加banner
     * @param Request $request
     * @return mixed|void
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data = [];
            $data['banner_title'] = $request->post('banner_title');
            $data['image_url'] = $request->post('image_url');
            $data['link_url'] = $request->post('link_url');
            $data['order_no'] = $request->post('order_no');
            $data['type'] = $request->post('type');
            $data['is_actived'] = $request->post('is_actived');

            $validate = new \app\admin\validate\Banner();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $data['create_time'] = time();

            $banner = new \app\admin\model\Banner();
            $banner->save($data);
            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }

    /**
     * 修改banner
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
            $banner_id = $request->post('banner_id');
            $data['banner_title'] = $request->post('banner_title');
            $data['image_url'] = $request->post('image_url');
            $data['link_url'] = $request->post('link_url');
            $data['order_no'] = $request->post('order_no');
            $data['type'] = $request->post('type');
            $data['is_actived'] = $request->post('is_actived');

            $validate = new \app\admin\validate\Banner();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $banner = new \app\admin\model\Banner();
            $banner->save($data, ['banner_id' => $banner_id]);
            echo $this->successJson();
            return;
        }

        $banner_id = $request->get('banner_id');
        if (empty($banner_id)) {
            $this->errorJson('关键数据错误');
        }
        $banner = \app\admin\model\Banner::where('banner_id', $banner_id)->find();

        $this->assign('banner', $banner);
        return $this->fetch();
    }

    /**
     * 删除banner
     * @param Request $request
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $banner_id = $request->post('banner_id');

            if (empty($banner_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            \app\admin\model\Banner::where('banner_id', $banner_id)->delete();
            echo $this->successJson();
            exit;
        }
    }

}
