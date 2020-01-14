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
    public function pay($pay)
    {
        empty($pay['order']) ? exit('订单号不能为空') : '';
        empty($pay['price']) ? exit('交易金额不能为空') : '';
        empty($pay['openid']) ? exit('openID不能为空') : '';
        empty($pay['body']) ? exit('商品描述不能为空') : '';

        $wechatInfo = $this->getWechatInfo();
        $appid = $wechatInfo['app_id'];
        $mch_id = $wechatInfo['mch_id'];
        $mch_key = $wechatInfo['mch_key'];

        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $data = array(
            'appid' => $appid,//1
            'mch_id' => $mch_id,//1
            'nonce_str' => $this->createNoncestr(),//1  随机字符串
            'sign_type' => 'MD5',//1
            'body' => $pay['body'],          //1  商品描述
            'out_trade_no' => $pay['order'],   //1  商户订单号
            'total_fee' => $pay['price'],        //1  交易金额，单位分
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],//1 终端IP
            'notify_url' => $pay['notify_url'],//1 异步通知地址
            'trade_type' => 'JSAPI',  //交易类型，公众号小程序支付jsapi
            'openid' => $pay['openid'],
        );
        $data['sign'] = $this->sign($data, $mch_key);
        $xml = $this->arrayToXml($data);
        $res = httpPost($url, $xml);
        $arr = $this->xmlToArray($res);
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
            'nonceStr' => $this->createNoncestr(),
            'package' => "prepay_id={$arr['prepay_id']}",
            'signType' => 'MD5',
        );
        $data['paySign'] = $this->sign($data, $mch_key);
        unset($data['appId']);
        return $data;
    }

    //微信支付 根据商户订单号查询订单
    public function queryOrder($order)
    {
        empty($order) ? exit('订单号不能为空') : '';

        $wechatInfo = $this->getWechatInfo();
        $appid = $wechatInfo['app_id'];
        $mch_id = $wechatInfo['mch_id'];
        $mch_key = $wechatInfo['mch_key'];

        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $data = array(
            'appid' => $appid,
            'mch_id' => $mch_id,
            'nonce_str' => $this->createNoncestr(),//随机字符串
            'out_trade_no' => $order,   //商户订单号
        );
        $data['sign'] = $this->sign($data, $mch_key);
        $xml = $this->arrayToXml($data);
        $res = httpPost($url, $xml);
        $arr = $this->xmlToArray($res);
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

    //创建随机字符串
    public function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    //生成签名
    public function sign($data, $mch_key)
    {
        ksort($data);
        $str = $this->toParamss($data);
        $str = $str . '&key=' . $mch_key;
        $str = md5($str);
        //将字符串转成大写
        $str = strtoupper($str);
        return $str;
    }

    //格式化参数成URL参数
    public function toParamss($data)
    {
        $buff = '';
        foreach ($data as $key => $value) {
            if ($key != 'sign' && $value != '' && !is_array($value)) {
                $buff .= $key . '=' . $value . '&';
            }
        }
        $buff = trim($buff, '&');
        return $buff;
    }

    //将xml转成数组
    public function xmlToArray($xml)
    {
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    //将数组转成xml
    public function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";

            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

}
