<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\admin\validate;


use think\Validate;

class Region extends Validate
{
    protected $rule = [
        'region_name' => 'require'
    ];

    protected $message = [
        'region_name.require' => '区域名称不能为空'
    ];
}
