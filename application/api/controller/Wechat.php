<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/7 0007
 * Time: 10:19
 */

namespace app\api\controller;

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
            recordLog('requestUrl-->' . $requestUrl, 'wechat.txt');

            $result_json_str = file_get_contents($requestUrl);
            recordLog('$result_json_str-->' . $result_json_str, 'wechat.txt');
            $result_json = json_decode($result_json_str, true);

            if (!empty($result_json['openid'])) {
                $openid = $result_json['openid'];
                $userInfo = \app\api\model\User::where(['open_id' => $openid])->find();

                $address = Db::table('mrs_user_address')->where(array('user_id' => $userInfo['user_id']))->order('is_default')->find();

                if ($userInfo) { //返回用户信息
                    $data['isauth'] = 1;
                    if (!empty($userInfo['face_img'])) {
                        $userInfo['face_img'] = config('domain') . $userInfo['face_img'];
                    }
                    $data['userInfo'] = $userInfo;
                    $data['address'] = $address;

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
                    $userInfo['create_time'] = time();

                    $userModel = new \app\api\model\User();
                    $user_id = $userModel->insert($userInfo);
                    $userInfo['user_id'] = $user_id;

                    $data['isauth'] = 0;
                    $data['userInfo'] = $userInfo;

                    echo $this->successJson($data);
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

            if (empty($user_id)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            $userInfo = \app\api\model\User::where(['user_id' => $user_id])->find();
            if (!$userInfo) {
                echo $this->errorJson(1, '未找到用户信息');
                exit;
            }

            $userModel = new \app\api\model\User();
            $userModel->save($data, ['user_id' => $user_id]);
            $userInfo['nick_name'] = $data['nick_name'];
            $userInfo['head_img'] = $data['head_img'];
            $userInfo['sex'] = $data['sex'];

            echo $this->successJson($userInfo);
            exit;
        }
    }

    //支付回调
    public function paynotice()
    {
        $param = file_get_contents('php://input');
        $data = xmlToArray($param);
        recordLog('data->'.json_encode($data), 'wechat.txt');
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
        recordLog('result->'.json_encode($result), 'wechat.txt');
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
                $actionData['action_remark'] = '用户【'.$order['user_name'].'】支付订单';
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
}
