<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\admin\validate;


use think\Validate;

class About extends Validate
{
    protected $rule = [
        'about_title' => 'require',
        'about_detail' => 'require'
    ];

    protected $message = [
        'about_title.require' => '标题不能为空',
        'about_detail.require' => '详情不能为空',
    ];
}
