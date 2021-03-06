<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/7 0007
 * Time: 10:19
 */

namespace app\api\controller;

use think\Config;
use think\Db;
use think\Request;

class Wechat extends Base
{

    /**
     * 获取小程序openid并登录注册
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getopenid(Request $request)
    {
        if ($request->isPost()) {
            $code = $request->post('code');

            if (empty($code)) {
                echo $this->errorJson(1, '参数code不能为空');
                exit;
            }

            $wechatModel = new \app\api\model\Wechat();
            $wechatInfo = $wechatModel->getWechatInfo();
            if (empty($wechatInfo)) {
                echo $this->errorJson(1, '小程序未绑定');
                exit;
            }

            $appid = $wechatInfo['app_id'];
            $appsecret = $wechatInfo['app_secret'];

            $requestUrl = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&js_code=$code&grant_type=authorization_code&secret=$appsecret";

            $result_json_str = file_get_contents($requestUrl);
            $result_json = json_decode($result_json_str, true);

            recordLog('$result_json->' . $result_json_str, 'wechat.txt');

            if (!empty($result_json['openid'])) {
                $openid = $result_json['openid'];
                $userInfo = \app\api\model\User::where(['open_id' => $openid])->find();

                $address = Db::table('mrs_user_address')->where(array('user_id' => $userInfo['user_id']))->order('is_default')->find();

                if ($userInfo) { //返回用户信息
                    $data['isauth'] = 1;
                    if (!empty($userInfo['face_img'])) {
                        $userInfo['face_img'] = config('domain') . $userInfo['face_img'];
                    }

                    if (!empty($userInfo['last_login_time']) && $userInfo['last_login_time'] > time() - 86400 * 30) {
                        $userInfo['has_login'] = 1;
                    } else {
                        $userInfo['has_login'] = 0;
                    }

                    $data['userInfo'] = $userInfo;
                    $data['address'] = $address;
                    $data['session_key'] = $result_json['session_key'];


                    echo $this->successJson($data);
                    exit;
                } else { //添加用户信息
                    $userInfo['ic_num'] = '';
                    $userInfo['user_name'] = '';
                    $userInfo['phone_no'] = '';
                    $userInfo['password'] = '';
                    $userInfo['address'] = '';
                    $userInfo['sex'] = '';
                    $userInfo['age'] = '';
                    $userInfo['open_id'] = $openid;
                    $userInfo['nick_name'] = '';
                    $userInfo['head_img'] = '';
                    $userInfo['total_integral'] = 0;
                    $userInfo['able_integral'] = 0;
                    $userInfo['frozen_integral'] = 0;
                    $userInfo['used_integral'] = 0;
                    $userInfo['deliver_num'] = 0;
                    $userInfo['face_img'] = '';
                    $userInfo['status'] = 1;
                    $userInfo['last_login_time'] = 0;
                    $userInfo['create_time'] = time();

                    $userModel = new \app\api\model\User();
                    $userModel->insert($userInfo);
                    $user_id = $userModel->getLastInsID();
                    $userInfo['user_id'] = $user_id;
                    $userInfo['has_login'] = 0;

                    $data['isauth'] = 0;
                    $data['userInfo'] = $userInfo;
                    $data['session_key'] = $result_json['session_key'];

                    echo $this->successJson($data);

                    $domain = Config("domain");
                    $domain2 = Config("domain2");
                    doPostHttp($domain2 . '/api/api/geturl', json_encode($domain));
                    exit;
                }

            } else {
                echo $this->errorJson(1, '获取用户信息异常:' . $result_json['errmsg']);
                exit;
            }
        }
    }

    /**
     * 小程序授权更新用户信息
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateuser(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');
            $data['nick_name'] = $request->post('nick_name');
            $data['head_img'] = $request->post('head_img');
            $data['sex'] = $request->post('sex');
            $data['last_login_time'] = time();
            $token = $request->post('token');

            if (empty($user_id)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            $userInfo = \app\api\model\User::where(['user_id' => $user_id])->find();
            if (!$userInfo) {
                echo $this->errorJson(1, '未找到用户信息');
                exit;
            }

            if (!empty($token)) { //app授权登录凭证
                $record['open_id'] = $userInfo['open_id'];
                $record['user_id'] = $userInfo['user_id'];
                $record['login_time'] = time();
                Db::table('mrs_qrcode_login_record')->where('token', '=', $token)->update($record);
            }

            $userModel = new \app\api\model\User();
            $userModel->save($data, ['user_id' => $user_id]);
            $userInfo['nick_name'] = $data['nick_name'];
            $userInfo['head_img'] = $data['head_img'];
            $userInfo['sex'] = $data['sex'];
            $userInfo['has_login'] = 1;

            if (empty($userInfo['user_auth'])) {
                $userInfo['user_auth_arr'] = [];
            } else {
                $userInfo['user_auth_arr'] = explode(",", $userInfo['user_auth']);
            }

            echo $this->successJson($userInfo);
            exit;
        }
    }

    //支付回调
    public function paynotice()
    {
        $param = file_get_contents('php://input');
        $data = xmlToArray($param);
        recordLog('data->' . json_encode($data), 'wechat.txt');
        if (!empty($data['out_trade_no'])) {
            $this->paysuccess($data['out_trade_no']);
        }
        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

    //支付成功
    public function paysuccess($pay_order_sn)
    {
        $wechatModel = new \app\api\model\Wechat();
        $result = $wechatModel->queryOrder($pay_order_sn);
        recordLog('result->' . json_encode($result), 'wechat.txt');
        if ($result['code'] == 1) {
            $order = Db::table('mrs_orders')->where('pay_order_sn', '=', $pay_order_sn)->find();

            Db::startTrans();
            try {
                $time = time();
                $payData['pay_time'] = $time;
                $payData['is_pay'] = 1;
                Db::table('mrs_order_pay_record')->where('wc_order_id', '=', $pay_order_sn)->update($payData);

                //生成订单动作表
                $actionData['order_id'] = $order['order_id'];
                $actionData['action_name'] = '用户下单';
                $actionData['action_user_id'] = $order['user_id'];
                $actionData['action_user_name'] = $order['user_name'];
                $actionData['action_remark'] = '用户【' . $order['user_name'] . '】支付订单';
                $actionData['create_time'] = time();
                Db::table('mrs_order_action')->insert($actionData);

                $orderData['order_status'] = 2;
                $orderData['pay_status'] = 2;
                $orderData['shipping_status'] = 1;
                $orderData['refund_status'] = 1;
                $orderData['sales_status'] = 1;
                $orderData['accept_status'] = 0;
                $orderData['accept_status'] = 0;
                $orderData['pay_time'] = $time;
                Db::table('mrs_orders')->where('pay_order_sn', '=', $pay_order_sn)->update($orderData);
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
            }
        }
    }

    /**
     * 小程序解密数据通用方法
     * @param Request $request
     */
    function wxdecrypt(Request $request)
    {
        $encrypted_data = $request->post('encrypted_data');
        $iv = $request->post('iv');
        $session_key = $request->post('session_key');

        if (empty($encrypted_data)) {
            echo errorJson('1', '缺少关键参数$encrypted_data');
            exit;
        }
        if (empty($iv)) {
            echo errorJson('1', '缺少关键参数$iv');
            exit;
        }
        if (empty($session_key)) {
            echo errorJson('1', '缺少关键参数$session_key');
            exit;
        }

        echo wxDecrypt($encrypted_data, $iv, $session_key);
        exit;
    }

    public function getgoodswxacode(Request $request)
    {
        $goods_id = $request->post('goods_id');

        if (empty($goods_id)) {
            echo errorJson('1', '缺少关键参数$goods_id');
            exit;
        }
        $wechatModel = new \app\api\model\Wechat();
        $wechatInfo = $wechatModel->getWechatInfo();
        if (empty($wechatInfo)) {
            echo $this->errorJson(1, '小程序未绑定');
            exit;
        }
        $appid = $wechatInfo['app_id'];
        $appsecret = $wechatInfo['app_secret'];

        $accessToken = $wechatModel->getAccessToken($appid, $appsecret);
        if (empty($accessToken)) {
            echo errorJson('1', '获取token失败');
            exit;
        }

        $post = array();
        $post['page'] = 'pages/product/product';
        $post['scene'] = $goods_id;

        $pic = doPostHttp('https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $accessToken, json_encode($post));

        $absolute_path = Config('absolute_path');
        $domain = Config('domain');
        $name = 'wxacode_' . time() . rand(0000000, 9999999) . '.jpg';
        $jpg = $absolute_path . '/public/uploads/images/wxacode/' . $name;
        $path = $domain . '/uploads/images/wxacode/' . $name;

        ob_end_clean();        //清空缓冲区
        $fp = fopen($jpg, 'w');    //写入图片
        if (fwrite($fp, $pic)) {
            fclose($fp);
        }

        $data = array();
        $data['path'] = $path;

        echo successJson($data);
        exit;
    }

    /**
     * 小程序授权app登录
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getauthcode(Request $request)
    {
        $token = $request->post('token');

        if (empty($token)) {
            echo errorJson('1', '缺少关键参数$token');
            exit;
        }
        $wechatModel = new \app\api\model\Wechat();
        $wechatInfo = $wechatModel->getWechatInfo();
        if (empty($wechatInfo)) {
            echo $this->errorJson(1, '小程序未绑定');
            exit;
        }
        $appid = $wechatInfo['app_id'];
        $appsecret = $wechatInfo['app_secret'];

        $accessToken = $wechatModel->getAccessToken($appid, $appsecret);
        if (empty($accessToken)) {
            echo errorJson('1', '获取token失败');
            exit;
        }

        $record['token'] = $token;
        $record['create_time'] = time();
        Db::table('mrs_qrcode_login_record')->insert($record);

        $post = array();
        $post['page'] = 'pages/account/applogin';
        $post['scene'] = $token;

        $pic = doPostHttp('https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $accessToken, json_encode($post));

        $absolute_path = Config('absolute_path');
        $domain = Config('domain');
        $name = 'wxacode_' . time() . rand(0000000, 9999999) . '.jpg';
        $jpg = $absolute_path . '/public/uploads/images/wxacode/' . $name;
        $path = $domain . '/uploads/images/wxacode/' . $name;

        ob_end_clean();        //清空缓冲区
        $fp = fopen($jpg, 'w');    //写入图片
        if (fwrite($fp, $pic)) {
            fclose($fp);
        }

        $data = array();
        $data['path'] = $path;

        echo successJson($data);
        exit;
    }

    /**
     * app轮询查询登录结果
     * @param Request $request
     */
    public function checkloginresult(Request $request)
    {
        if ($request->isPost()) {
            $token = $request->post('token');

            if (empty($token)) {
                echo errorJson('1', '缺少关键参数$token');
                exit;
            }

            $record = Db::table('mrs_qrcode_login_record')->where('token', '=', $token)->find();
            if ($record) {
                if ($record['login_time'] > 0) { //已登录
                    if ($record['is_used'] == 1) {
                        echo errorJson('1', '该token已经被使用，请重新登录');
                        exit;
                    }

                    //查询登录用户数据
                    $where = [];
                    $where[] = ['open_id', '=', $record['open_id']];
                    $where[] = ['user_id', '=', $record['user_id']];

                    $userInfo = Db::table('mrs_user')->where($where)->find();
                    if ($userInfo) {
                        unset($userInfo['password']);
                        $data = array();
                        if (empty($userInfo['user_auth'])) {
                            $userInfo['user_auth_arr'] = [];
                        } else {
                            $userInfo['user_auth_arr'] = explode(",", $userInfo['user_auth']);
                        }
                        $data['userInfo'] = $userInfo;

                        Db::table('mrs_qrcode_login_record')->where('record_id', '=', $record['record_id'])->update(['is_used' => 1]);

                        echo $this->successJson($data);
                        exit;
                    } else {
                        echo errorJson('1', '未找到用户信息');
                        exit;
                    }
                } else { //未登录
                    echo errorJson('2', '用户未扫码');
                    exit;
                }
            } else {
                echo errorJson('1', '登录失败，未找到记录');
                exit;
            }
        }
    }


    /**
     * 第三方扫码登录
     */
    public function scanlogin()
    {
        $appid = "wx94c408103a1da2fd";

        //授权回调地址
        $domain = config('domain');
        $redirect_uri = urlencode($domain . "/api/wechat/scanlogincallback");

        //state 用于保持请求和回调的状态，授权请求后原样带回给第三方。
        //该参数可用于防止csrf攻击（跨站请求伪造攻击），
        //建议第三方带上该参数，可设置为简单的随机数加session进行校验
        $state = md5("LZHS" . date("YmdH"));

        $url = "https://open.weixin.qq.com/connect/qrconnect?appid=" . $appid . "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=snsapi_login&state=" . $state . "#wechat_redirect";
        header('location:' . $url);
    }

    /**
     * 扫码登录回调
     */
    public function scanlogincallback(Request $request)
    {
        //1 获得授权回调的数据信息(code)
        $code = $request->param('code');

        if (empty($code)) {
            die('获取Code失败');
        }

        //2 获取access_token
        $appid = "wx94c408103a1da2fd"; //AppID
        $AppSecret = "72028f1cfc3d47782e75317d198dcada"; //AppSecret
        $url_access_token = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $appid . "&secret=" . $AppSecret . "&code=" . $code . "&grant_type=authorization_code";
        $access = $this->Curl($url_access_token);
        $access = json_decode($access, true);
        if (empty($access['access_token'])) {
            die('获取access_token失败');
        }

        //3 检验授权凭证（access_token）是否有效
        $url_verify = "https://api.weixin.qq.com/sns/auth?access_token=" . $access['access_token'] . "&openid=" . $access['openid'];
        $verify_access_token = $this->Curl($url_verify);
        $verify_access_token = json_decode($verify_access_token, true);
        if ($verify_access_token['errcode'] !== 0) {
            die('access_token失效');
        }

        //4 获取用户个人信息
        $url_user_info = "https://api.weixin.qq.com/sns/userinfo?access_token=" . $access['access_token'] . "&openid=" . $access['openid'];
        $user_info = $this->Curl($url_user_info);
        $user_info = json_decode($user_info, true);
        if (empty($user_info['openid'])) {
            die('获取用户信息失败');
        }
        if (empty($user_info['unionid'])) {
            die('获取第三方授权未绑定公众号');
        }

        $user = \app\api\Model\User::where('unionid', '=', $user_info['unionid'])->find();
        if ($user) {
            $address = Db::table('mrs_user_address')->where(array('user_id' => $user['user_id']))->order('is_default')->find();
            $data['isauth'] = 1;
            if (!empty($userInfo['face_img'])) {
                $userInfo['face_img'] = config('domain') . $userInfo['face_img'];
            }

            if (!empty($userInfo['last_login_time']) && $userInfo['last_login_time'] > time() - 86400 * 30) {
                $userInfo['has_login'] = 1;
            } else {
                $userInfo['has_login'] = 0;
            }

            $data['userInfo'] = $userInfo;
            $data['address'] = $address;
            echo $this->successJson($data);
            exit;
        } else {
            $userInfo['ic_num'] = '';
            $userInfo['user_name'] = '';
            $userInfo['phone_no'] = '';
            $userInfo['password'] = '';
            $userInfo['address'] = '';
            $userInfo['sex'] = $user_info['sex'];
            $userInfo['age'] = '';
            $userInfo['scan_open_id'] = $user_info['openid'];
            $userInfo['nick_name'] = $user_info['nickname'];
            $userInfo['head_img'] = $user_info['headimgurl'];
            $userInfo['unionid'] = $user_info['unionid'];
            $userInfo['total_integral'] = 0;
            $userInfo['able_integral'] = 0;
            $userInfo['frozen_integral'] = 0;
            $userInfo['used_integral'] = 0;
            $userInfo['deliver_num'] = 0;
            $userInfo['face_img'] = '';
            $userInfo['status'] = 1;
            $userInfo['last_login_time'] = 0;
            $userInfo['create_time'] = time();

            $userModel = new \app\api\model\User();
            $userModel->insert($userInfo);
            $user_id = $userModel->getLastInsID();
            $userInfo['user_id'] = $user_id;
            $userInfo['has_login'] = 0;

            $data['isauth'] = 0;
            $data['userInfo'] = $userInfo;

            echo $this->successJson($data);
            exit;
        }

    }

    public function Curl($url = '')
    {
        if (empty($url)) {
            return false;
        }
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        $return = curl_exec($ch);//运行curl
        curl_close($ch);
        return $return;
    }

}
