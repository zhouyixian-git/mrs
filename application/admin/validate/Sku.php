<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\admin\validate;


use think\Validate;

class Sku extends Validate
{
    protected $rule = [
        'sku_name' => 'require'
    ];

    protected $message = [
        'sku_name.require' => '规格名称不能为空'
    ];
}
