<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/7 0007
 * Time: 20:23
 */

namespace app\admin\controller;


class Index extends Base
{
    public function index()
    {
        return $this->fetch();
    }

    public function home()
    {
        return $this->fetch();
    }
}