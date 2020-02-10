<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\admin\validate;


use think\Validate;

class Banner extends Validate
{
    protected $rule = [
        'banner_title' => 'require|max:32',
        'link_url' => 'url',
        'type'=>'require',
    ];

    protected $message = [
        'banner_title.require' => 'banner标题不能为空',
        'banner_title.max' => 'banner标题不能超过32字符',
        'link_url.url' => 'banner链接格式错误',
        'type.require' => '请选择轮播图类型'
    ];
}
