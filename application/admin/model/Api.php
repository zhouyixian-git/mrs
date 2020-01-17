<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:11
 */

namespace app\admin\model;


use think\Model;
use think\Db;

class Api extends Model
{
    //设置数据表名
    protected $table = 'mrs_api';

    /**
     * 获取参数S
     * @param string $api_code
     * @return array|bool
     */
    public function getParams($api_code = ''){
        if(!empty($api_code)){
            $api = Db::table('mrs_api')->where(array('api_code', '=', $api_code))->find();

            $params = Db::table("mrs_api_params")->where('api_id', '=', $api['api_id'])->select();
            $data = array();
            $data['api'] = $api;
            $data['params'] = $params;

            return $data;
        }
        return false;
    }

    /**
     * 获取参数
     * @param string $api_code
     * @return array|bool
     */
    public function getParam($api_code = '', $param_code = ''){
        if(!empty($api_code) && !empty($param_code)){
            $api = Db::table('mrs_api')->where('api_code', '=', $api_code)->find();

            $where = array();
            $where[] = ['api_id', '=', $api['api_id']];
            $where[] = ['param_code', '=', $param_code];
            $param = Db::table("mrs_api_params")->where($where)->find();

            return $param['param_value'];
        }
        return false;
    }
}
