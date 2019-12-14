<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/9 0009
 * Time: 21:48
 */

namespace app\controller;


use think\Controller;
use think\Db;
use think\facade\Cache;
use think\facade\Session;

class Base extends Controller
{
    protected static $_ADMINID = null;
    public static $loginAdmin = null;

    protected function initialize()
    {
        parent::initialize();
        //过滤不需要登陆的行为
        $allowUrl = ['/login/login', '/login/logout', '/login/verify'];
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
                self::$loginAdmin = $admin;

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

    /**
     * 获取角色菜单
     * @param $role_id
     * @param $admin_code
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenu($role_id, $admin_code)
    {
        if ($admin_code == 'admin') {
            $parentMenu = Db::table('eas_menu')
                ->field('menu_id,menu_name,menu_url,menu_icon,menu_level')
                ->where([['menu_level', '=', 1]])
                ->order('order_no asc')
                ->select();
        } else {
            $parentMenu = Db::table('eas_role_menu')
                ->alias('t1')
                ->field('t2.menu_id,t2.menu_name,t2.menu_url,t2.menu_icon,t2.menu_level')
                ->leftJoin('eas_menu t2', 't1.menu_id = t2.menu_id')
                ->where([['t1.role_id', '=', $role_id], ['t2.menu_level', '=', 1], ['t2.menu_code', '<>', 'menu_mgr']])
                ->order('t2.order_no asc')
                ->select();
        }

        if ($admin_code == 'admin') {
            $childMenu = Db::table('eas_menu')
                ->field('menu_id,menu_name,menu_url,menu_icon,parent_id,menu_level')
                ->where([['menu_level', '=', 2]])
                ->order('order_no asc')
                ->select();
        } else {
            $childMenu = Db::table('eas_role_menu')
                ->alias('t1')
                ->field('t2.menu_id,t2.menu_name,t2.menu_url,t2.menu_icon,t2.parent_id,t2.menu_level')
                ->leftJoin('eas_menu t2', 't1.menu_id = t2.menu_id')
                ->where([['t1.role_id', '=', $role_id], ['t2.menu_level', '=', 2]])
                ->order('t2.order_no asc')
                ->select();
        }

        $menuData = [];
        foreach ($parentMenu as $k => $v) {
            $childMenuData = [];
            foreach ($childMenu as $k1 => $v1) {
                if ($v['menu_id'] == $v1['parent_id']) {
                    $childMenuData[] = [
                        'menu_name' => $v1['menu_name'],
                        'menu_url' => $v1['menu_url'],
                        'menu_id' => $v1['menu_id'],
                        'menu_level' => $v1['menu_level'],
                        'menu_icon' => $v1['menu_icon']
                    ];
                }
            }
            $menuData[] = [
                'menu_name' => $v['menu_name'],
                'menu_url' => $v['menu_url'],
                'menu_id' => $v['menu_id'],
                'menu_level' => $v['menu_level'],
                'menu_icon' => $v['menu_icon'],
                'child' => $childMenuData
            ];
        }
        return $menuData;
    }

    /**
     * 获取按钮权限
     * @param $role_id
     * @param $admin_code
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getButton($role_id, $admin_code){
        if ($admin_code == 'admin') {
            $button = Db::table('eas_menu')
                ->field('menu_code,menu_name')
                ->where([['menu_level', '=', 3]])
                ->order('order_no asc')
                ->select();
        } else {
            $button = Db::table('eas_role_menu')
                ->alias('t1')
                ->field('t2.menu_code,t2.menu_name')
                ->leftJoin('eas_menu t2', 't1.menu_id = t2.menu_id')
                ->where([['t1.role_id', '=', $role_id], ['t2.menu_level', '=', 3]])
                ->order('t2.order_no asc')
                ->select();
        }
        return  $button;
    }

}
