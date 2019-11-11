<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/8 0008
 * Time: 20:49
 */

namespace app\controller;


use think\Exception;
use think\Request;

class Menu extends Base
{

    public function index(Request $request){
        $parentMenuList = \app\model\Menu::where('parent_id', '0')->order('order_no', 'asc')->select();
        $this->assign('parentMenuList', $parentMenuList);
        return $this->fetch();
    }

    public function add(Request $request){
        if($request->isPost()){
            $menu_code = $request->post('menu_code');
            $menu_name = $request->post('menu_name');
            $menu_url = $request->post('menu_url');
            $menu_icon = $request->post('menu_icon');
            $menu_level = $request->post('menu_level');
            $parent_id = $request->post('parent_id');
            $order_no = $request->post('order_no');

            if($menu_level == 1){
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
                echo $this->errorJson(0,$validate->getError());
                return;
            }

            $menu = new \app\model\Menu();
            $menu->save($data);
            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }

    public function edit(Request $request){
        if($request->isPost()){

        }
    }

}
