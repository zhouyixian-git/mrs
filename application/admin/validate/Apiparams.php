<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\admin\validate;


use think\Validate;

class Apiparams extends Validate
{
    protected $rule = [
        'param_name' => 'require|max:32',
        'param_code' => 'require',
        'param_desc' => 'require'
    ];

    protected $message = [
        'param_name.require' => '参数名称不能为空',
        '.require.require' => '参数编码不能为空',
        'param_name.max' => '参数名称不能超过32字符',
        'param_desc.require' => '参数描述不能为空'
    ];
}
