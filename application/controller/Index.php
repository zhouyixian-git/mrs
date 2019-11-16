<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/7 0007
 * Time: 20:23
 */

namespace app\controller;


use think\Controller;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }
}
