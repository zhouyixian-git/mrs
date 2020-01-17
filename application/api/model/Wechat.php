<?php


namespace app\api\model;


use think\Db;
use think\facade\Cache;
use think\Model;

class Wechat extends Model
{

    //设置数据表名
    protected $table = 'mrs_wechats';

    /**
     * 获取小程序信息
     * @return array|mixed|null|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWechatInfo()
    {
        //查询微信公众号信息
        $wechatInfo = Cache::get('wechatInfo');
        if (empty($wechatInfo)) {
            $where = [];
            $where[] = ['is_actived', '=', 1];
            $wechatInfo = Wechat::where($where)->find();
            if (empty($wechatInfo)) {
                return [];
            } else {
                Cache::set('wechatInfo', $wechatInfo);
            }
        }
        return $wechatInfo;
    }

    //小程序支付
    public function doPay($pay)
    {
        if (empty($pay['pay_order_sn'])) {
            $data['errcode'] = 1;
            $data['errmsg'] = '支付订单号不能为空';
            return $data;
        }
        if (empty($pay['order_amount'])) {
            $data['errcode'] = 1;
            $data['errmsg'] = '交易金额不能为空';
            return $data;
        }
        if (empty($pay['open_id'])) {
            $data['errcode'] = 1;
            $data['errmsg'] = 'openid不能为空';
            return $data;
        }
        if (empty($pay['body'])) {
            $data['errcode'] = 1;
            $data['errmsg'] = '商品描述不能为空';
            return $data;
        }

        $wechatInfo = $this->getWechatInfo();
        $appid = $wechatInfo['app_id'];
        $mch_id = $wechatInfo['mch_id'];
        $mch_key = $wechatInfo['mch_key'];

        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $data = array(
            'appid' => $appid,//1
            'mch_id' => $mch_id,//1
            'nonce_str' => createNoncestr(),//1  随机字符串
            'sign_type' => 'MD5',//1
            'body' => $pay['body'],          //1  商品描述
            'out_trade_no' => $pay['pay_order_sn'],   //1  商户订单号
            'total_fee' => $pay['order_amount'] * 100,        //1  交易金额，单位分
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],//1 终端IP
            'notify_url' => $pay['notify_url'],//1 异步通知地址
            'trade_type' => 'JSAPI',  //交易类型，公众号小程序支付jsapi
            'openid' => $pay['open_id'],
        );
        $data['sign'] = sign($data, $mch_key);
        $xml = arrayToXml($data);
        $res = httpPost($url, $xml);
        $arr = xmlToArray($res);
        if (!empty($arr['err_code_des'])) {
            $data['errcode'] = 1;
            $data['errmsg'] = $arr['err_code_des'];
            return $data;
        }
        if ($arr['return_code'] == 'FAIL') {
            $data['errcode'] = 1;
            $data['errmsg'] = $arr['return_msg'];
            return $data;
        }
        $time = time();
        $data = array(
            'appId' => $appid,
            'timeStamp' => "$time",
            'nonceStr' => createNoncestr(),
            'package' => "prepay_id={$arr['prepay_id']}",
            'signType' => 'MD5',
        );
        $data['paySign'] = sign($data, $mch_key);
        unset($data['appId']);
        return $data;
    }

    //微信支付 根据商户订单号查询订单
    public function queryOrder($pay_order_sn)
    {
        empty($pay_order_sn) ? exit('订单号不能为空') : '';

        $wechatInfo = $this->getWechatInfo();
        $appid = $wechatInfo['app_id'];
        $mch_id = $wechatInfo['mch_id'];
        $mch_key = $wechatInfo['mch_key'];

        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $data = array(
            'appid' => $appid,
            'mch_id' => $mch_id,
            'nonce_str' => createNoncestr(),//随机字符串
            'out_trade_no' => $pay_order_sn,   //商户订单号
        );
        $data['sign'] = sign($data, $mch_key);
        $xml = arrayToXml($data);
        $res = httpPost($url, $xml);
        $arr = xmlToArray($res);
        if (!empty($arr['transaction_id']) && $arr['trade_state'] == 'SUCCESS') {
            $arr['code'] = 1;
            $arr['pay_time'] = strtotime($arr['time_end']);
        } else {
            $arr['code'] = 0;
        }
        // transaction_id   交易号
        // pay_time  支付时间
        // code   状态
        return $arr;
    }



}
