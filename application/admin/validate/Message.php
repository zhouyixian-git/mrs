<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\admin\validate;


use think\Validate;

class Message extends Validate
{
    protected $rule = [
        'msg_title' => 'require',
        'msg_content' => 'require'
    ];

    protected $message = [
        'msg_title.require' => '消息标题不能为空',
        'msg_content.require' => '消息内容不能为空',
    ];
}
