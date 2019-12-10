<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\validate;


use think\Validate;

class Wechat extends Validate
{
    protected $rule = [
        'wechat_name' => 'require|max:32',
        'app_id' => 'require',
        'app_secret' => 'require'
    ];

    protected $message = [
        'wechat_name.require' => '小程序名称不能为空',
        'wechat_name.max' => '小程序名称不能超过32字符',
        'app_id.require' => '小程序app_id不能为空',
        'app_secret.require' => '小程序app_secret不能为空'
    ];
}
