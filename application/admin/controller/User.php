<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/19 0019
 * Time: 22:53
 */

namespace app\admin\controller;


use think\Db;
use think\Request;

class User extends Base
{

    /**
     * 用户列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
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
            $where[] = ['user_name', 'like', "%$user_name%"];
        }
        if (!empty($phone_no)) {
            $where[] = ['phone_no', 'like', "%$phone_no%"];
        }
        if (!empty($nick_name)) {
            $where[] = ['nick_name', 'like', "%$nick_name%"];
        }
        if (!empty($ic_num)) {
            $where[] = ['ic_num', 'like', "%$ic_num%"];
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

    /**
     * 修改用户类型
     * @param Request $request
     */
    public function updateusertype(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');

            if (empty($user_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            $user = Db::table('mrs_user')->where('user_id', '=', $user_id)->find();
            if (!$user) {
                echo $this->errorJson(0, '未找到用户信息');
                exit;
            }

            Db::table('mrs_user')->where('user_id', '=', $user_id)->update(['user_type' => 2]);
            echo $this->successJson();
            exit;
        }
    }

    /**
     * 配置用户权限
     * @param Request $request
     * @return mixed
     */
    public function auth(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');
            $user_auth_arr = $request->post('user_auth');

            if (empty($user_id)) {
                echo $this->errorJson(0, '缺少关键参数user_id');
                exit;
            }

            if (empty($user_auth_arr)) {
                echo $this->errorJson(0, '请先选择权限模块');
                exit;
            }

            $user_auth = implode(",", $user_auth_arr);

            Db::table('mrs_user')->where('user_id', '=', $user_id)->update(['user_auth' => $user_auth]);
            echo $this->successJson();
            exit;
        }

        $user_id = $request->get('user_id');
        if (empty($user_id)) {
            $this->error('关键数据错误');
        }

        $user_auth = Db::table('mrs_user')->where('user_id', '=', $user_id)->value('user_auth');

        if (empty($user_auth)) {
            $this->assign('user_auth_arr', []);
        } else {
            $user_auth_arr = explode(",", $user_auth);
            $this->assign('user_auth_arr', $user_auth_arr);
        }
        $this->assign('user_id', $user_id);
        return $this->fetch();
    }

    /**
     * 用户积分流水
     * @param Request $request
     */
    public function integral(Request $request)
    {
        $user_id = $request->param('user_id');
        $where = [];
        if (!empty($user_id)) {
            $where[] = ['user_id', '=', $user_id];
        }

        $integralList = Db::table('mrs_integral_detail')
            ->where($where)
            ->order('create_time desc')
            ->paginate(6, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $integralList->render();
        $count = Db::table('mrs_integral_detail')->where($where)->count();

        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('integralList', $integralList);
        return $this->fetch();
    }

}
