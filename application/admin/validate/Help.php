<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\admin\validate;


use think\Validate;

class Help extends Validate
{
    protected $rule = [
        'help_title' => 'require',
        'help_detail' => 'require'
    ];

    protected $message = [
        'help_title.require' => '标题不能为空',
        'help_detail.require' => '详情不能为空',
    ];
}
