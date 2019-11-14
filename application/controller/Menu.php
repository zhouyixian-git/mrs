<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/8 0008
 * Time: 20:49
 */

namespace app\controller;


use think\Request;

class Menu extends Base
{

    public function index(Request $request)
    {
        $parentMenuList = \app\model\Menu::where('parent_id', '0')->order('order_no', 'asc')->select();
        $childMenuList = \app\model\Menu::where('parent_id', '>', '0')->order('order_no', 'asc')->select();

        $menuData = [];
        foreach ($parentMenuList as $k => $v) {
            $childMenuData = [];
            foreach ($childMenuList as $k1 => $v1) {
                if ($v['menu_id'] == $v1['parent_id']) {
                    $childMenuData[] = ['menu_id' => $v1['menu_id'], 'text' => $v1['menu_name']];
                }
            }
            $menuData[] = ['text' => $v['menu_name'], 'nodes' => $childMenuData, 'menu_id' => $v['menu_id']];
        }

        $this->assign('menuList', json_encode($menuData));  //这里将菜单数据转成json字符串，因为前端js无法赋值
        $this->assign('parentMenuList', $parentMenuList);
        return $this->fetch();
    }

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

    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $menu_code = $request->post('menu_code');
            $menu_name = $request->post('menu_name');
            $menu_url = $request->post('menu_url');
            $menu_icon = $request->post('menu_icon');
            $order_no = $request->post('order_no');
            $menu_id = $request->post('menu_id');

            if(empty($menu_id)){
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

    public function delete(Request $request){
        if($request->isPost()){
            $menu_id = $request->post('menu_id');
            if (empty($menu_id)) {
                echo $this->errorJson(0, '关键参数错误！');
                exit;
            }

            \app\model\Menu::where('menu_id', '=',$menu_id)->whereOr('parent_id', '=', $menu_id)->delete();
            echo $this->successJson();
            return;
        }
    }

}
