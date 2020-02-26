<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/5 0005
 * Time: 20:12
 */

namespace app\api\controller;


use think\Db;
use think\Request;

class Api extends Base
{
    /**
     * 第三方接口参数获取
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getApiParam(Request $request)
    {
        if ($request->isPost()) {
            $api_code = $request->post('api_code');
            if(empty($api_code)){
                echo $this->errorJson('1','缺少关键参数api_code');
                exit;
            }

            $api = Db::table("mrs_api")->where('api_code','=',$api_code)->find();

            if(empty($api)){
                echo $this->errorJson('1','该参数对应接口配置不存在');
            }

            $apiParam = Db::table("mrs_api_params")->where('api_id','=',$api['api_id'])->select();

            $data = array();
            $data = $api;
            $data['api_params'] = $apiParam;

            echo $this->successJson($data);
            exit;

        }
    }
}
