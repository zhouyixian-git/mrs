<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/11 0011
 * Time: 20:39
 */

namespace app\admin\validate;


use think\Validate;

class Callrecoverycate extends Validate
{

    protected $rule = [
        'cate_name' => 'require'
    ];

    protected $message = [
        'cate_name.require' => '分类名称不能为空'
    ];

}
