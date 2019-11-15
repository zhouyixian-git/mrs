<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/11 0011
 * Time: 20:39
 */

namespace app\validate;


use think\Validate;

class Role extends Validate
{

    protected $rule = [
        'role_code' => 'require|max:30',
        'role_name' => 'require'
    ];

    protected $message = [
        'role_code.require' => '角色编码不能为空',
        'role_code.max' => '角色编码不能超过30字符',
        'role_name.require' => '角色名称不能为空'
    ];

}
