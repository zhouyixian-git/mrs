<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/11 0011
 * Time: 20:39
 */

namespace app\validate;


use think\Validate;

class Admin extends Validate
{

    protected $rule = [
        'admin_code' => 'require|max:30',
        'admin_name' => 'require',
        'admin_pwd' => 'require|confirm:confirm_admin_pwd|min:6',
        'role_id' => 'require',
        'admin_email' => 'email',
        'admin_mobile' => 'mobile'
    ];

    protected $message = [
        'admin_code.require' => '成员账号不能为空',
        'admin_code.max' => '成员账号不能超过30字符',
        'admin_name.require' => '成员名称不能为空',
        'admin_pwd.require' => '成员密码不能为空',
        'admin_pwd.min' => '密码必须6个字符以上',
        'admin_pwd.confirm' => '两次输入的密码不一致',
        'role_id.require' => '请选择成员角色',
        'admin_email.email' => '邮箱格式错误',
        'admin_mobile.mobile' => '手机号格式错误'
    ];

    protected $scene = [
        'edit' => ['admin_code', 'admin_name', 'role_id', 'admin_email', 'admin_mobile'],
        'updatepwd' => ['admin_pwd']
    ];

}
