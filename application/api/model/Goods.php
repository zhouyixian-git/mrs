<?php


namespace app\api\model;


use think\Db;
use think\Model;

class Goods extends Model
{

    //设置数据表名
    protected $table = 'mrs_goods';

    /**
     * 减库存
     * @param $goods_id     商品id
     * @param $goods_num    购买数量
     * @param $sku_json     库存json
     */
    public function cutstock($goods_id, $goods_num, $sku_json)
    {
        $sku_arr = json_decode($sku_json, true);
        $sku_count = count($sku_arr);
        $detail_id = 0;
        if ($sku_count == 1) { //单规格
            $sku_id = $sku_arr[0]['sku_id'];
            $sku_details = Db::table('mrs_goods_sku_detail')
                ->field('detail_id,sku_json')
                ->where('goods_id', '=', $goods_id)
                ->select();
            foreach ($sku_details as $k => $v) {
                $sku_json = json_decode($v['sku_json'], true);
                $unionId = $sku_json['skuInfo']['unionId'];
                if ($unionId == $sku_id) {
                    $detail_id = $v['detail_id'];
                    continue;
                }
            }
        } else if ($sku_count == 2) { //双规格
            $sku_id_1 = $sku_arr[0]['sku_id'];
            $sku_id_2 = $sku_arr[1]['sku_id'];
            $sku_arr = [$sku_id_1 . '_' . $sku_id_2, $sku_id_2 . '-' . $sku_id_1];
            $sku_details = Db::table('mrs_goods_sku_detail')
                ->field('detail_id,sku_json')
                ->where('goods_id', '=', $goods_id)
                ->select();
            foreach ($sku_details as $k => $v) {
                $sku_json = json_decode($v['sku_json'], true);
                $unionId = $sku_json['skuInfo']['unionId'];
                if (in_array($unionId, $sku_arr)) {
                    $detail_id = $v['detail_id'];
                    continue;
                }
            }
        } else if ($sku_count == 3) { //三规格
            $sku_id_1 = $sku_arr[0]['sku_id'];
            $sku_id_2 = $sku_arr[1]['sku_id'];
            $sku_id_3 = $sku_arr[2]['sku_id'];
            $sku_arr = [
                $sku_id_1 . '_' . $sku_id_2 . '_' . $sku_id_3,
                $sku_id_1 . '_' . $sku_id_3 . '_' . $sku_id_2,
                $sku_id_2 . '_' . $sku_id_1 . '_' . $sku_id_3,
                $sku_id_2 . '_' . $sku_id_3 . '_' . $sku_id_1,
                $sku_id_3 . '_' . $sku_id_1 . '_' . $sku_id_2,
                $sku_id_3 . '_' . $sku_id_2 . '_' . $sku_id_1
            ];
            $sku_details = Db::table('mrs_goods_sku_detail')
                ->field('detail_id,sku_json')
                ->where('goods_id', '=', $goods_id)
                ->select();
            foreach ($sku_details as $k => $v) {
                $sku_json = json_decode($v['sku_json'], true);
                $unionId = $sku_json['skuInfo']['unionId'];
                if (in_array($unionId, $sku_arr)) {
                    $detail_id = $v['detail_id'];
                    continue;
                }
            }
        }
        if (!empty($detail_id)) {
            //减库存
            $goods_detail = Db::table('mrs_goods_sku_detail')->where('detail_id', '=', $detail_id)->field('goods_stock,sku_json')->find();
            $new_stock = bcsub($goods_detail['goods_stock'], 1);
            $new_stock = bcmul($new_stock, $goods_num);
            $sku_json = json_decode($goods_detail['sku_json'], true);
            if ($new_stock <= 0) {
                $new_stock = 0;
            }
            $sku_json['skuInfo']['goodsStock'] = $new_stock;
            $stockData['goods_stock'] = $new_stock;
            $stockData['sku_json'] = json_encode($sku_json);
            Db::table('mrs_goods_sku_detail')->where('detail_id', '=', $detail_id)->update($stockData);
        }
    }

    /**
     * 增库存
     * @param $order_id
     */
    public function addstock($order_id)
    {
        $orderGoods = Db::table('mrs_order_goods')->where('order_id', '=', $order_id)->field('goods_id,goods_num,sku_json,sku_detail_id')->select();
        foreach ($orderGoods as $k => $v) {
            $sku_arr = json_decode(json_decode($v['sku_json'], true), true);
            $sku_count = count($sku_arr);
            $sku_details = Db::table('mrs_goods_sku_detail')
                ->field('detail_id,sku_json,goods_stock')
                ->where('goods_id', '=', $v['goods_id'])
                ->select();
            if ($sku_count == 1) { //单规格
                $detail_id = 0;
                $new_stock = 0;
                $sku_id = $sku_arr[0]['sku_id'];
                $new_sku_json = array();
                foreach ($sku_details as $key => $value){
                    $sku_json = json_decode($value['sku_json'], true);
                    $unionId = $sku_json['skuInfo']['unionId'];
                    if ($unionId == $sku_id) {
                        $new_sku_json = $sku_json;
                        $detail_id = $value['detail_id'];
                        $goods_stock = $value['goods_stock'];
                        $new_stock = bcadd($goods_stock, $v['goods_num']);
                        $new_sku_json['skuInfo']['goodsStock'] = $new_stock;
                        continue;
                    }
                }
                if(!empty($detail_id)){
                    $stockData['goods_stock'] = $new_stock;
                    $stockData['sku_json'] = json_encode($new_sku_json);
                    Db::table('mrs_goods_sku_detail')->where('detail_id', '=', $detail_id)->update($stockData);
                }

            } else if ($sku_count == 2) { //双规格
                $detail_id = 0;
                $new_stock = 0;
                $new_sku_json = array();
                $sku_id_1 = $sku_arr[0]['sku_id'];
                $sku_id_2 = $sku_arr[1]['sku_id'];
                $sku_arr = [$sku_id_1 . '_' . $sku_id_2, $sku_id_2 . '-' . $sku_id_1];
                foreach ($sku_details as $key => $value){
                    $sku_json = json_decode($value['sku_json'], true);
                    $unionId = $sku_json['skuInfo']['unionId'];
                    if (in_array($unionId, $sku_arr)) {
                        $new_sku_json = $sku_json;
                        $detail_id = $value['detail_id'];
                        $goods_stock = $value['goods_stock'];
                        $new_stock = bcadd($goods_stock, $v['goods_num']);
                        $new_sku_json['skuInfo']['goodsStock'] = $new_stock;
                        continue;
                    }
                }
                if(!empty($detail_id)){
                    $stockData['goods_stock'] = $new_stock;
                    $stockData['sku_json'] = json_encode($new_sku_json);
                    Db::table('mrs_goods_sku_detail')->where('detail_id', '=', $detail_id)->update($stockData);
                }
            } else if ($sku_count == 3) { //三规格
                $detail_id = 0;
                $new_stock = 0;
                $new_sku_json = array();
                $sku_id_1 = $sku_arr[0]['sku_id'];
                $sku_id_2 = $sku_arr[1]['sku_id'];
                $sku_id_3 = $sku_arr[2]['sku_id'];
                $sku_arr = [
                    $sku_id_1 . '_' . $sku_id_2 . '_' . $sku_id_3,
                    $sku_id_1 . '_' . $sku_id_3 . '_' . $sku_id_2,
                    $sku_id_2 . '_' . $sku_id_1 . '_' . $sku_id_3,
                    $sku_id_2 . '_' . $sku_id_3 . '_' . $sku_id_1,
                    $sku_id_3 . '_' . $sku_id_1 . '_' . $sku_id_2,
                    $sku_id_3 . '_' . $sku_id_2 . '_' . $sku_id_1
                ];
                foreach ($sku_details as $key => $value){
                    $sku_json = json_decode($value['sku_json'], true);
                    $unionId = $sku_json['skuInfo']['unionId'];
                    if (in_array($unionId, $sku_arr)) {
                        $new_sku_json = $sku_json;
                        $detail_id = $value['detail_id'];
                        $goods_stock = $value['goods_stock'];
                        $new_stock = bcadd($goods_stock, $v['goods_num']);
                        $new_sku_json['skuInfo']['goodsStock'] = $new_stock;
                        continue;
                    }
                }
                if(!empty($detail_id)){
                    $stockData['goods_stock'] = $new_stock;
                    $stockData['sku_json'] = json_encode($new_sku_json);
                    Db::table('mrs_goods_sku_detail')->where('detail_id', '=', $detail_id)->update($stockData);
                }
            }
        }
    }
}
