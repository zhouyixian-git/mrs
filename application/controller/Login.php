<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/16 0016
 * Time: 19:59
 */

namespace app\controller;


use think\facade\Cache;
use think\facade\Session;
use think\Request;

class Login extends Base
{
    public function index()
    {
        $admin = Session::get('admin');
        if ($admin['admin_id']) {
            return $this->redirect('/index');
        }
    }

    /**
     * 登录
     * @param Request $request
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login(Request $request)
    {

        $admin = Session::get('admin');
        if ($admin['admin_id']) {
            return $this->redirect('/index');
        }

        if ($request->isPost()) {
            $admin_code = $request->post('user_name');
            $admin_pwd = $request->post('password');

            $loginAdmin = \app\model\Admin::where([['admin_code', '=', $admin_code], ['admin_status', '=', 1]])->find();
            if (!$loginAdmin) {
                echo $this->errorJson(0, '用户不存在！');
                return;
            }

            if (md5($admin_code . $admin_pwd . $admin_code) != $loginAdmin['admin_pwd']) {
                echo $this->errorJson(0, '用户名或密码错误！');
                return;
            }

            $role_name = \app\model\Role::where('role_id',$loginAdmin['role_id'])->value('role_name');

            //查询菜单
            $menuData = $this->getMenu($loginAdmin['role_id']);
            if(empty($menuData)){
                echo $this->errorJson(0, '您没有后台操作权限，请联系管理员！');
                return;
            }
            $loginAdmin['menuData'] = $menuData;

            Cache::inc('SinglePoint_' . $loginAdmin['admin_id']);
            $loginAdmin['role_name'] = $role_name;
            $loginAdmin['single_point'] = Cache::get('SinglePoint_' . $loginAdmin['admin_id']);
            Session::set('admin', $loginAdmin);

            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }

    /**
     * 退出登录
     */
    public function logout(){
        parent::logout();
    }
}
