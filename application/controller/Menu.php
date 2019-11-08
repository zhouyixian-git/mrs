<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/8 0008
 * Time: 20:49
 */

namespace app\controller;


use think\Controller;

class Menu extends Controller
{

    public function index(){
        return $this->fetch();
    }

}
