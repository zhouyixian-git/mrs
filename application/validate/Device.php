<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\validate;


use think\Validate;

class Device extends Validate
{
    protected $rule = [
        'device_name' => 'require|max:255',
        'device_code' => 'require',
        'site_id' => 'require',
        'lng' => 'require',
        'lat' => 'require'
    ];

    protected $message = [
        'device_name.require' => '设备名称不能为空',
        'device_name.max' => '设备名称不能超过255字符',
        'device_code.require' => '设备编码不能为空',
        'site_id.require' => '请选择站点信息',
        'lng.require' => '请选择设备坐标',
        'lat.require' => '请选择设备坐标'
    ];
}
