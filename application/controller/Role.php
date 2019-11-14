<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/14 0014
 * Time: 20:50
 */

namespace app\controller;


use think\Request;

class Role extends Base
{

    public function index(){
        $roleList = \app\model\Role::order('role_id', 'desc')->select();
        $this->assign('roleList', $roleList);
        return $this->fetch();
    }

    public function add(){

    }

    public function edit(Request $request){
        if($request->isPost()){
            echo json_encode("");
            exit;
        }
    }

    public function delete(){

    }

}
