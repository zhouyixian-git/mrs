<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/20 0020
 * Time: 12:49
 */

namespace app\admin\validate;


use think\Validate;

class Goods extends Validate
{
    protected $rule = [
        'cate_id' => 'require',
        'goods_name' => 'require',
        'goods_desc' => 'require',
        'goods_price' => 'require|float',
        'max_cash' => 'float',
        'goods_detail' => 'require'
    ];

    protected $message = [
        'cate_id.require' => '请选择商品分类',
        'goods_name.require' => '商品名称不能为空',
        'goods_desc.require' => '商品描述不能为空',
        'goods_price.require' => '商品价格不能为空',
        'goods_price.float' => '商品价格格式错误',
        'max_cash.float' => '现金最大限额格式错误',
        'goods_detail.require' => '商品详情不能为空',
    ];
}
