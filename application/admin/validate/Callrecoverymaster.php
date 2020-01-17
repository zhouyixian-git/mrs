<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\admin\validate;


use think\Validate;

class Callrecoverymaster extends Validate
{
    protected $rule = [
        'master_name' => 'require|max:32',
        'master_phone_no' => 'require|mobile',
        'address' => 'require',
        'lat' => 'require',
        'lng' => 'require'
    ];

    protected $message = [
        'master_name.require' => '上门人员名称不能为空',
        'master_phone_no.require' => '上门人员联系号码不能为空',
        'master_name.max' => '上门人员名称不能超过32字符',
        'master_phone_no.mobile' => '上门人员联系号码格式错误',
        'address.require' => '地址信息不能为空',
        'lat.require' => '请选择坐标',
        'lng.require' => '请选择坐标'
    ];
}
