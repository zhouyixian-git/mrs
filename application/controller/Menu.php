<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/8 0008
 * Time: 20:49
 */

namespace app\controller;


use app\model\RoleMenu;
use think\Request;

class Menu extends Base
{

    /**
     * 菜单列表
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $menu = new \app\model\Menu();
        $menuData = $menu->getMenuTree(0);

        $parentMenuList = $menu->getParentMenu(0);

        $this->assign('menuList', json_encode($menuData));  //这里将菜单数据转成json字符串，因为前端js无法赋值
        $this->assign('parentMenuList', $parentMenuList);
        return $this->fetch();
    }

    /**
     * 添加菜单
     * @param Request $request
     * @return mixed|void
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $menu_code = $request->post('menu_code');
            $menu_name = $request->post('menu_name');
            $menu_url = $request->post('menu_url');
            $menu_icon = $request->post('menu_icon');
            $menu_level = $request->post('menu_level');
            $parent_id = $request->post('parent_id');
            $order_no = $request->post('order_no');

            if ($menu_level == 1) {
                $parent_id = 0;
            }

            $data = [
                'menu_code' => $menu_code,
                'menu_name' => $menu_name,
                'menu_url' => $menu_url,
                'menu_icon' => $menu_icon,
                'menu_level' => $menu_level,
                'parent_id' => $parent_id,
                'order_no' => $order_no
            ];
            $validate = new \app\validate\Menu();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $menu = new \app\model\Menu();
            $menu->save($data);
            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }

    /**
     * 编辑菜单
     * @param Request $request
     */
    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $menu_code = $request->post('menu_code');
            $menu_name = $request->post('menu_name');
            $menu_url = $request->post('menu_url');
            $menu_icon = $request->post('menu_icon');
            $order_no = $request->post('order_no');
            $menu_id = $request->post('menu_id');

            if (empty($menu_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            $data = [
                'menu_code' => $menu_code,
                'menu_name' => $menu_name,
                'menu_url' => $menu_url,
                'menu_icon' => $menu_icon,
                'order_no' => $order_no
            ];
            $validate = new \app\validate\Menu();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $menu = new \app\model\Menu();
            $menu->save($data, ['menu_id' => $menu_id]);
            echo $this->successJson();
            return;
        }
    }

    /**
     * 根据菜单id查询菜单
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenuById(Request $request)
    {
        if ($request->isPost()) {
            $menu_id = $request->post('menu_id');
            if (empty($menu_id)) {
                echo $this->errorJson(0, '关键参数错误！');
                exit;
            }

            $menuInfo = \app\model\Menu::where('menu_id', $menu_id)->find();
            echo $this->successJson($menuInfo);
            return;
        }
    }

    /**
     * 删除菜单
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $menu_id = $request->post('menu_id');
            if (empty($menu_id)) {
                echo $this->errorJson(0, '关键参数错误！');
                exit;
            }

            $roleMenuCount = RoleMenu::where('menu_id', $menu_id)->count();
            if($roleMenuCount > 0){
                echo $this->errorJson(0, '该菜单已经被使用，不可删除！');
                exit;
            }

            \app\model\Menu::where('menu_id', '=', $menu_id)->whereOr('parent_id', '=', $menu_id)->delete();
            echo $this->successJson();
            return;
        }
    }

}
