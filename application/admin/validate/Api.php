<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\admin\validate;


use think\Validate;

class Api extends Validate
{
    protected $rule = [
        'api_name' => 'require|max:32',
        'api_desc' => 'require',
        'api_address' => 'require|url',
        'api_code' => 'require',
        'status' => 'require'
    ];

    protected $message = [
        'api_name.require' => '接口名称不能为空',
        'api_code' => '接口编码不能为空',
        'api_name.max' => '接口名称不能超过32字符',
        'api_desc.require' => '接口描述不能为空',
        'api_address.require' => '请输入接口地址',
        'api_address.url' => '接口地址格式不正确',
        'status.require' => '请选择接口状态'
    ];
}
