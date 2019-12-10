<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\validate;


use think\Validate;

class GoodsCate extends Validate
{
    protected $rule = [
        'cate_name' => 'require|max:255'
    ];

    protected $message = [
        'cate_name.require' => '分类名称不能为空',
        'cate_name.max' => '分类名称不能超过32字符'
    ];
}
