<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/9 0009
 * Time: 21:48
 */

namespace app\controller;


use think\Controller;

class Base extends Controller
{


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
