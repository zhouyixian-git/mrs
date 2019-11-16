<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/9 0009
 * Time: 21:48
 */

namespace app\controller;


use think\Controller;
use think\facade\Cache;
use think\facade\Session;

class Base extends Controller
{
    protected static $_ADMINID = null;

    protected function initialize()
    {
        parent::initialize();
        //过滤不需要登陆的行为
        $allowUrl = ['/login/login', '/login/logout'];
        $rule = strtolower($this->request->module() . '/' . $this->request->controller() . '/' . $this->request->action());
        if (in_array($rule, $allowUrl)) {
        } else {
            $admin = Session::get('admin');
            if ($admin['admin_id']) {
                $single_point = Cache::get('SinglePoint_' . $admin['admin_id']);
                if (empty($single_point) || $admin['single_point'] != $single_point) {
                    return $this->logout();
                }
                self::$_ADMINID = $admin['admin_id'];

                //查询菜单

                $this->assign('loginAdmin', $admin);

            } else {
                return $this->redirect("/login/login");
            }
        }
    }

    /**
     * Notes: 退出登录
     */
    public function logout()
    {
        if (self::$_ADMINID) {
            Cache::rm('SinglePoint_' . self::$_ADMINID);
        }
        Session::delete('admin');
        return $this->redirect("/login/login");
    }

    /**
     * 返回成功信息
     * @param null $data
     * @return false|string|void
     */
    public function successJson($data = null)
    {
        $result = [];
        $result['errcode'] = '1';
        $result['msg'] = 'success';
        $result['data'] = $data;
        return json_encode($result);
    }

    /**
     * 返回错误信息
     * @param int $errcode
     * @param string $message
     * @return false|string|void
     */
    public function errorJson($errcode = 0, $message = 'error')
    {
        $result = [];
        $result['errcode'] = $errcode;
        $result['msg'] = $message;
        return json_encode($result);
    }

}
