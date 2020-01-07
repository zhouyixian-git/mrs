<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/5 0005
 * Time: 20:12
 */

namespace app\api\controller;

use think\Controller;
use think\Db;
use think\facade\Cache;
use think\facade\Session;

class Base extends Controller
{

    public function __construct(App $app = null)
    {
        parent::__construct($app);
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Methods:POST,GET');
    }

    /**
     * 返回成功信息
     * @param null $data
     * @return false|string|void
     */
    public function successJson($data = null)
    {
        $result = [];
        $result['errcode'] = 0;
        $result['msg'] = 'success';
        if(!empty($data)) {
            $result['data'] = $data;
        }
        return json_encode($result);
    }

    /**
     * 返回错误信息
     * @param int $errcode
     * @param string $message
     * @return false|string|void
     */
    public function errorJson($errcode = 1, $message = 'error')
    {
        $result = [];
        $result['errcode'] = $errcode;
        $result['msg'] = $message;
        return json_encode($result);
    }
}
