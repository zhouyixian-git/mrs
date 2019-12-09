<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\validate;


use think\Validate;

class Banner extends Validate
{
    protected $rule = [
        'banner_title' => 'require|max:32',
        'image_url' => 'require',
        'link_url' => 'url'
    ];

    protected $message = [
        'banner_title.require' => 'banner标题不能为空',
        'banner_title.max' => 'banner标题不能超过32字符',
        'image_url.require' => 'banner图片不能为空',
        'link_url.url' => 'banner链接格式错误'
    ];
}
