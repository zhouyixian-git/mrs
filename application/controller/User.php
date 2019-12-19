<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/19 0019
 * Time: 22:53
 */

namespace app\controller;


use think\Db;
use think\Request;

class User extends Base
{

    /**
     * 用户列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request){
        $status = $request->param('status');
        $user_name = $request->param('user_name');
        $ic_num = $request->param('ic_num');
        $phone_no = $request->param('phone_no');
        $nick_name = $request->param('nick_name');
        $where = [];
        if (!empty($status)) {
            $where[] = ['status', '=', $status];
        }
        if (!empty($user_name)) {
            $where[] = ['user_name', '=', $user_name];
        }
        if (!empty($phone_no)) {
            $where[] = ['phone_no', '=', $phone_no];
        }
        if (!empty($nick_name)) {
            $where[] = ['nick_name', '=', $nick_name];
        }

        $userList = Db::table('mrs_user')
            ->where($where)
            ->order('create_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $userList->render();
        $count = Db::table('mrs_user')->where($where)->count();

        $this->assign('status', $status);
        $this->assign('user_name', $user_name);
        $this->assign('ic_num', $ic_num);
        $this->assign('phone_no', $phone_no);
        $this->assign('nick_name', $nick_name);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('userList', $userList);
        return $this->fetch();
    }

}
