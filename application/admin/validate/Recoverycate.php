<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/11 0011
 * Time: 20:39
 */

namespace app\admin\validate;


use think\Validate;

class Recoverycate extends Validate
{

    protected $rule = [
        'cate_name' => 'require'
    ];

    protected $message = [
        'cate_name.require' => '分类名称不能为空',
        'bg_icon_img.require' => '请上传对应图标',
        'unit_weight.require' => '请输入单位重量'
    ];

}
