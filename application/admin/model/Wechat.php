<?php


namespace app\admin\model;


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

}
