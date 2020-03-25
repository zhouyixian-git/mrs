<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/11 0011
 * Time: 22:08
 */

namespace app\admin\controller;


use app\lib\event\PushEvent;
use think\Controller;
use think\Request;

class Test extends Controller
{
    /**
     * 推送一个字符串
     */
    public function pushmsg(Request $request)
    {
        $string = $request->param('content');
        $string = input('msg') ? : $string;
        $push = new PushEvent();
        $push->setUser(123456)->setContent($string)->push();
    }

    /**
     * 推送目标页
     *
     * @return \think\response\View
     */
    public function index()
    {
        return view();
    }

}
