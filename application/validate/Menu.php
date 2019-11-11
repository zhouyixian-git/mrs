<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/11 0011
 * Time: 20:39
 */

namespace app\validate;


use think\Validate;

class Menu extends Validate
{

    protected $rule = [
        'menu_code' => 'require|max:30',
        'menu_name' => 'require'
    ];

    protected $message = [
        'menu_code.require' => '菜单编码不能为空',
        'menu_code.max' => '菜单编码不能超过30字符',
        'menu_name.require' => '菜单名称不能为空'
    ];

}
