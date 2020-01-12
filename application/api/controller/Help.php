<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/12 0012
 * Time: 9:42
 */

namespace app\api\controller;


use think\Db;
use think\Request;

class Help extends Base
{

    /**
     * 获取帮助列表
     * @param Request $request
     */
    public function getHelpList(Request $request)
    {
        if ($request->isPost()) {
            $page = $request->post('page');

            if (empty($page)) {
                $page = 1;
            }
            $pageSize = 10;

            $helpList = Db::table('mrs_help')
                ->where('is_actived', '=', 1)
                ->field('help_id,help_title')
                ->order('order_no asc,create_time desc')
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select();

            $totalCount = Db::table('mrs_help')
                ->where('is_actived', '=', 1)
                ->count();

            if ($helpList) {
                $total_page = ceil($totalCount / $pageSize);

                $data['helpList'] = $helpList;
                $data['total_page'] = $total_page;
                echo $this->successJson($data);
                exit;
            } else {
                echo $this->errorJson(1, '没有账单信息');
                exit;
            }
        }
    }

    /**
     * 帮助详情
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getHelpDetail(Request $request)
    {
        if ($request->isPost()) {
            $help_id = $request->post('help_id');

            if (empty($help_id)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            $help = Db::table('mrs_help')
                ->field('help_id,help_title,help_detail,create_time')
                ->where('help_id', '=', $help_id)
                ->find();
            if ($help) {
                $help['create_time'] = date('Y-m-d H:i', $help['create_time']);

                echo $this->successJson($help);
                exit;
            } else {
                echo $this->errorJson(1, '没有帮助信息');
                exit;
            }
        }
    }

}