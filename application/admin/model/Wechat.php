<?php


namespace app\admin\model;


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

    /**
     * 企业付款到个人
     * @param $data
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function transfers($param)
    {
        if (empty($param['partner_trade_no'])) {
            $ret['errcode'] = 1;
            $ret['errmsg'] = '商户订单号不能为空';
            return $ret;
        }
        if (empty($param['amount'])) {
            $ret['errcode'] = 1;
            $ret['errmsg'] = '提现金额不能为空';
            return $ret;
        }
        if (empty($param['openid'])) {
            $ret['errcode'] = 1;
            $ret['errmsg'] = 'openid不能为空';
            return $ret;
        }
        if (empty($param['desc'])) {
            $ret['errcode'] = 1;
            $ret['errmsg'] = '付款备注不能为空';
            return $ret;
        }

        $wechatInfo = $this->getWechatInfo();
        $appid = $wechatInfo['app_id'];
        $mch_id = $wechatInfo['mch_id'];
        $mch_key = $wechatInfo['mch_key'];

        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $data = array(
            'mch_appid' => $appid,
            'mchid' => $mch_id,
            'nonce_str' => createNoncestr(),
            'partner_trade_no' => $param['partner_trade_no'],
            'openid' => $param['openid'],
            'check_name' => 'NO_CHECK',
            'amount' => $param['amount'] * 100,
            'desc' => $param['desc'],
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR']
        );
        $data['sign'] = sign($data, $mch_key);
        $xml = arrayToXml($data);
        $res = httpPostCert($url, $xml);
        $arr = xmlToArray($res);
        if (!empty($arr['err_code_des'])) {
            $ret['errcode'] = 1;
            $ret['errmsg'] = $arr['err_code_des'];
            return $ret;
        }
        if ($arr['return_code'] == 'FAIL') {
            $ret['errcode'] = 1;
            $ret['errmsg'] = $arr['return_msg'];
            return $ret;
        }

        $ret['partner_trade_no'] = $arr['partner_trade_no'];
        $ret['payment_no'] = $arr['payment_no'];
        $ret['payment_time'] = $arr['payment_time'];
        return $ret;
    }

    /**
     * 微信支付退款
     * @param $param
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refund($param)
    {
        if (empty($param['out_trade_no'])) {
            $ret['errcode'] = 1;
            $ret['errmsg'] = '商户订单号不能为空';
            return $ret;
        }
        if (empty($param['out_refund_no'])) {
            $ret['errcode'] = 1;
            $ret['errmsg'] = '退款订单号不能为空';
            return $ret;
        }
        if (empty($param['amount'])) {
            $ret['errcode'] = 1;
            $ret['errmsg'] = '退款金额不能为空';
            return $ret;
        }

        $wechatInfo = $this->getWechatInfo();
        $appid = $wechatInfo['app_id'];
        $mch_id = $wechatInfo['mch_id'];
        $mch_key = $wechatInfo['mch_key'];

        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        $data = array(
            'appid' => $appid,
            'mch_id' => $mch_id,
            'nonce_str' => createNoncestr(),
            'out_trade_no' => $param['out_trade_no'],
            'out_refund_no' => $param['out_refund_no'],
            'total_fee' => $param['amount'] * 100,
            'refund_fee' => $param['amount'] * 100
        );
        $data['sign'] = sign($data, $mch_key);
        $xml = arrayToXml($data);
        $res = httpPostCert($url, $xml);
        recordLog($res, 'Wechat.txt');
        $arr = xmlToArray($res);
        recordLog('arr->' . json_encode($arr), 'Wechat.txt');
        if (!empty($arr['err_code_des'])) {
            $ret['errcode'] = 1;
            $ret['errmsg'] = $arr['err_code_des'];
            return $ret;
        }
        if ($arr['return_code'] == 'FAIL') {
            $ret['errcode'] = 1;
            $ret['errmsg'] = $arr['return_msg'];
            return $ret;
        }

        $data = array(
            'errcode' => 0,
            'out_trade_no' => $arr['out_trade_no'],
            'out_refund_no' => $arr['out_refund_no']
        );
        return $data;
    }

    /**
     * 查询退款订单
     * @param $out_refund_no
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function queryRefundOrder($out_refund_no)
    {
        if (empty($pay_order_sn)) {
            $ret['errcode'] = 1;
            $ret['errmsg'] = '退款单号不能为空';
            return $ret;
        }

        $wechatInfo = $this->getWechatInfo();
        $appid = $wechatInfo['app_id'];
        $mch_id = $wechatInfo['mch_id'];
        $mch_key = $wechatInfo['mch_key'];

        $url = 'https://api.mch.weixin.qq.com/pay/refundquery';
        $data = array(
            'appid' => $appid,
            'mch_id' => $mch_id,
            'nonce_str' => createNoncestr(),//随机字符串
            'out_refund_no' => $out_refund_no,   //退款订单号
        );
        $data['sign'] = sign($data, $mch_key);
        $xml = arrayToXml($data);
        $res = httpPost($url, $xml);
        $arr = xmlToArray($res);
        recordLog(json_encode($arr), 'wechat.txt');
        if (!empty($arr['transaction_id']) && $arr['trade_state'] == 'SUCCESS') {
            $arr['errcode'] = 0;
            $arr['errmsg'] = 'success';
            $arr['pay_time'] = strtotime($arr['time_end']);
        } else {
            $arr['errcode'] = 1;
        }
        // transaction_id   交易号
        // pay_time  支付时间
        // code   状态
        return $arr;
    }
}
