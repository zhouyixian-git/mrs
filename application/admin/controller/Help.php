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

class Help extends Base
{

    /**
     * 帮助中心列表
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $is_actived = $request->param('is_actived');
        $help_title = $request->param('help_title');
        $where = [];
        if (!empty($is_actived)) {
            $where[] = ['is_actived', '=', $is_actived];
        }
        if (!empty($help_title)) {
            $where[] = ['help_title', 'like', "%$help_title%"];
        }

        $helpList = Db::table('mrs_help')
            ->where($where)
            ->order('order_no asc,create_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $helpList->render();
        $count = Db::table('mrs_help')->where($where)->count();

        $this->assign('is_actived', $is_actived);
        $this->assign('help_title', $help_title);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('helpList', $helpList);
        return $this->fetch();
    }

    /**
     * 添加帮助中心
     * @param Request $request
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data = [];
            $data['help_title'] = $request->post('help_title');
            $data['help_detail'] = $request->post('help_detail');
            $data['order_no'] = $request->post('order_no');
            $data['is_actived'] = $request->post('is_actived');

            $validate = new \app\admin\validate\Help();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $data['create_time'] = time();

            $help = new \app\admin\model\Help();
            $help->save($data);
            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }

    /**
     * 修改帮助中心
     * @param Request $request
     */
    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $data = [];
            $help_id = $request->post('help_id');
            $data['help_title'] = $request->post('help_title');
            $data['help_detail'] = $request->post('help_detail');
            $data['order_no'] = $request->post('order_no');
            $data['is_actived'] = $request->post('is_actived');

            $validate = new \app\admin\validate\Help();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $help = new \app\admin\model\Help();
            $help->save($data, ['help_id' => $help_id]);
            echo $this->successJson();
            return;
        }

        $help_id = $request->get('help_id');
        if (empty($help_id)) {
            $this->errorJson('关键数据错误');
        }
        $help = \app\admin\model\Help::where('help_id', $help_id)->find();

        $this->assign('help', $help);
        return $this->fetch();
    }

    /**
     * 删除帮助中心
     * @param Request $request
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $help_id = $request->post('help_id');

            if (empty($help_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            \app\admin\model\Help::where('help_id', $help_id)->delete();
            echo $this->successJson();
            exit;
        }
    }
}
