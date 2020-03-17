<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\admin\validate;


use think\Validate;

class Site extends Validate
{
    protected $rule = [
        'site_name' => 'require|max:255',
        'lng' => 'require',
        'lat' => 'require',
        'site_address' => 'require',
        'region_id' => 'require'
        /*'province_id' => 'require',
        'city_id' => 'require',
        'area_id' => 'require'*/
    ];

    protected $message = [
        'site_name.require' => '站点名称不能为空',
        'site_name.max' => '站点名称不能超过255字符',
        'lng.require' => '请选择站点坐标',
        'lat.require' => '请选择站点坐标',
        'site_address.require' => '站点地址不能为空',
        'region_id.require' => '站点区域不能为空'
        /*'province_id.require' => '请选择站点区域',
        'city_id.require' => '请选择站点区域',
        'area_id.require' => '请选择站点区域'*/
    ];
}
