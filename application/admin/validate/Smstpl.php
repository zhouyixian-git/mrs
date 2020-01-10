<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\admin\validate;


use think\Validate;

class Smstpl extends Validate
{
    protected $rule = [
        'tpl_name' => 'require|max:32',
        'tpl_code' => 'require',
        'aliyun_code' => 'require',
        'tpl_content' => 'require'
    ];

    protected $message = [
        'tpl_name.require' => '模板名称不能为空',
        'tpl_code.require' => '模板编码不能为空',
        'tpl_name.max' => '模板名称不能超过32字符',
        'aliyun_code.require' => '阿里云模板编码不能为空',
        'tpl_content.require' => '请输入模板内容'
    ];
}
