<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/5 0005
 * Time: 21:21
 */

namespace app\api\controller;


use think\Db;
use think\Request;

class User extends Base
{

    /**
     * 获取用户地址列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addresslist(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');

            if (empty($user_id)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            $addressList = Db::table('mrs_user_address')
                ->where('user_id', '=', $user_id)
                ->field('address_id,consignee,telephone,province_name,city_name,district_name,areainfo,address,is_default')
                ->order('is_default asc,create_time desc')
                ->select();

            if ($addressList) {
                echo $this->successJson($addressList);
                exit;
            } else {
                echo $this->errorJson(1, '没有地址信息');
                exit;
            }
        }
    }

    /**
     * 修改用户地址
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editaddress(Request $request)
    {
        if ($request->isPost()) {
            $address_id = $request->post('address_id');
            $data['consignee'] = $request->post('consignee');
            $data['telephone'] = $request->post('telephone');
            $data['zip_code'] = $request->post('zip_code');
            /*$data['province_name'] = $request->post('province_name');
            $data['city_name'] = $request->post('city_name');
            $data['district_name'] = $request->post('district_name');*/
            $data['areainfo'] = $request->post('areainfo');
            $data['address'] = $request->post('address');
            $data['remark'] = $request->post('remark');
            $data['is_default'] = $request->post('is_default');

            if (empty($address_id)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }
            if (empty($data['consignee'])) {
                echo $this->errorJson(1, '请填写收货人信息');
                exit;
            }
            if (empty($data['telephone'])) {
                echo $this->errorJson(1, '请填写手机号码信息');
                exit;
            }
            /*if (empty($data['province_name']) || empty($data['city_name']) || empty($data['district_name'])) {
                echo $this->errorJson(1, '请填写地区信息');
                exit;
            }*/
            if (empty($data['areainfo'])) {
                echo $this->errorJson(1, '请填写地区信息');
                exit;
            }
            if (empty($data['address'])) {
                echo $this->errorJson(1, '请填写详细地址');
                exit;
            }
            if (empty($data['zip_code'])) {
                echo $this->errorJson(1, '请填写邮政号码');
                exit;
            }

            Db::table('mrs_user_address')
                ->where('address_id', '=', $address_id)
                ->update($data);

            echo $this->successJson();
            exit;
        }
    }

    /**
     * 添加用户地址
     * @param Request $request
     */
    public function addaddress(Request $request)
    {
        if ($request->isPost()) {
            $data['user_id'] = $request->post('user_id');
            $data['consignee'] = $request->post('consignee');
            $data['telephone'] = $request->post('telephone');
            $data['zip_code'] = $request->post('zip_code');
            /*$data['province_name'] = $request->post('province_name');
            $data['city_name'] = $request->post('city_name');
            $data['district_name'] = $request->post('district_name');*/
            $data['address'] = $request->post('address');
            $data['areainfo'] = $request->post('areainfo');
            $data['remark'] = $request->post('remark');
            $data['is_default'] = $request->post('is_default');

            if (empty($data['user_id'])) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }
            if (empty($data['consignee'])) {
                echo $this->errorJson(1, '请填写收货人信息');
                exit;
            }
            if (empty($data['telephone'])) {
                echo $this->errorJson(1, '请填写手机号码信息');
                exit;
            }
            /*if (empty($data['province_name']) || empty($data['city_name']) || empty($data['district_name'])) {
                echo $this->errorJson(1, '请填写地区信息');
                exit;
            }*/
            if (empty($data['areainfo'])) {
                echo $this->errorJson(1, '请填写地区信息');
                exit;
            }
            if (empty($data['address'])) {
                echo $this->errorJson(1, '请填写详细地址');
                exit;
            }
            if (empty($data['zip_code'])) {
                echo $this->errorJson(1, '请填写邮政号码');
                exit;
            }

            $data['country'] = '中国';
            $data['create_time'] = time();
            $res = Db::table('mrs_user_address')->insert($data);
            if ($res) {
                echo $this->successJson();
                exit;
            } else {
                echo $this->errorJson(0, '添加地址失败');
                exit;
            }
        }
    }

    /**
     * 获取购物车列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cartlist(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');

            if (empty($user_id)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            $goodsList = Db::table('mrs_carts')
                ->where('user_id', '=', $user_id)
                ->field('cart_id,goods_id,goods_name,goods_image,goods_price,is_check,goods_num,sku_json')
                ->order('create_time desc')
                ->select();

            if ($goodsList) {
                $domain = config('domain');
                foreach ($goodsList as $k => $v) {
                    $goodsList[$k]['goods_image'] = $domain . $v['goods_image'];
                    $goods_sku = '';
                    if (!empty($v['sku_json'])) {
                        $skuJson = json_decode(json_decode($v['sku_json'], true), true);
                        if (!empty($skuJson)) {
                            foreach ($skuJson as $key => $value) {
                                $goods_sku .= $value['sku_name'] . '-';
                            }
                        }
                        $goods_sku = substr($goods_sku, 0, -1);
                    }
                    $goodsList[$k]['goods_sku'] = $goods_sku;
                }
                echo $this->successJson($goodsList);
                exit;
            } else {
                echo $this->errorJson(1, '购物车没有商品信息');
                exit;
            }
        }
    }

    /**
     * 购物车商品选中
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function cartcheck(Request $request)
    {
        if ($request->isPost()) {
            $cart_id = $request->post('cart_id');
            $is_check = $request->post('is_check');

            if (empty($cart_id) || empty($is_check)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            Db::table('mrs_carts')
                ->where('cart_id', '=', $cart_id)
                ->update(['is_check' => $is_check]);

            echo $this->successJson();
            exit;
        }
    }

    /**
     * 购物车商品购买数量
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function cartaddnum(Request $request)
    {
        if ($request->isPost()) {
            $cart_id = $request->post('cart_id');
            $goods_num = $request->post('goods_num');

            if (empty($cart_id) || empty($goods_num)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            Db::table('mrs_carts')
                ->where('cart_id', '=', $cart_id)
                ->update(['goods_num' => $goods_num]);

            echo $this->successJson();
            exit;
        }
    }

    /**
     * 购物车商品删除
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function cartdelete(Request $request)
    {
        if ($request->isPost()) {
            $cart_id = $request->post('cart_id');

            if (empty($cart_id)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            Db::table('mrs_carts')
                ->where('cart_id', '=', $cart_id)
                ->delete();

            echo $this->successJson();
            exit;
        }
    }

    /**
     * 用户头像修改
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editheadimg(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');
            $head_img = $request->post('head_img');

            if (empty($user_id) || empty($head_img)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            Db::table('mrs_user')
                ->where('user_id', '=', $user_id)
                ->update(['head_img' => $head_img]);

            echo $this->successJson();
            exit;
        }
    }

    /**
     * 用户昵称修改
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editnickname(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');
            $nick_name = $request->post('nick_name');

            if (empty($user_id) || empty($nick_name)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            Db::table('mrs_user')
                ->where('user_id', '=', $user_id)
                ->update(['nick_name' => $nick_name]);

            echo $this->successJson();
            exit;
        }
    }

    /**
     * 用户手机号修改
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editphoneno(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');
            $phone_no = $request->post('phone_no');
            $vcode = $request->post('vcode');

            if (empty($user_id) || empty($phone_no) || empty($vcode)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            Db::table('mrs_user')
                ->where('user_id', '=', $user_id)
                ->update(['phone_no' => $phone_no]);

            echo $this->successJson();
            exit;
        }
    }

    /**
     * 用户性别修改
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editsex(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');
            $sex = $request->post('sex');

            if (empty($user_id) || empty($sex)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            Db::table('mrs_user')
                ->where('user_id', '=', $user_id)
                ->update(['sex' => $sex]);

            echo $this->successJson();
            exit;
        }
    }

    /**
     * 用户人脸修改
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editfaceimg(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');
            $face_img = $request->post('face_img');

            if (empty($user_id) || empty($face_img)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            Db::table('mrs_user')
                ->where('user_id', '=', $user_id)
                ->update(['face_img' => $face_img]);

            //更新人脸库
            $userM = new \app\api\model\User();
            $userM->faceset($user_id);

            echo $this->successJson();
            exit;
        }
    }

    /**
     * 用户密码修改
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function editpassword(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');
            $phone_no = $request->post('phone_no');
            $password = $request->post('password');
            $confirm_password = $request->post('confirm_password');
            $token = $request->post('token');

            if (empty($user_id) || empty($phone_no) || empty($password) || empty($confirm_password)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            if ($password != $confirm_password) {
                echo $this->errorJson(1, '密码不一致');
                exit;
            }
            $res = checkToken($token);
            $result = json_decode($res, true);
            if ($result['errcode'] == '1') {
                echo $res;
                exit;
            }


            $where = [];
            $where[] = ['user_id', '=', $user_id];
            $where[] = ['phone_no', '=', $phone_no];
            $password = md5($user_id . md5($password));
            Db::table('mrs_user')
                ->where($where)
                ->update(['password' => $password]);

            echo $this->successJson();
            exit;
        }
    }

    /**
     * 我的消息列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function msglist(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');

            if (empty($user_id)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            $msgList = Db::table('mrs_system_msg')
                ->where('is_actived', '=', 1)
                ->field('msg_id,msg_title,msg_content,create_time')
                ->order('create_time desc')
                ->select();

            if ($msgList) {
                foreach ($msgList as $k => $v) {
                    $msgList[$k]['create_time'] = date('Y-m-d H:i', $v['create_time']);
                }
                echo $this->successJson($msgList);
                exit;
            } else {
                echo $this->errorJson(1, '没有消息信息');
                exit;
            }
        }
    }

    /**
     * 消息详情
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function msgdetail(Request $request)
    {
        if ($request->isPost()) {
            $msg_id = $request->post('msg_id');

            if (empty($msg_id)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            $msg = Db::table('mrs_system_msg')
                ->where('msg_id', '=', $msg_id)
                ->field('msg_id,msg_title,msg_content,create_time')
                ->order('create_time desc')
                ->find();

            if ($msg) {
                $msg['create_time'] = date('Y-m-d H:i', $msg['create_time']);

                echo $this->successJson($msg);
                exit;
            } else {
                echo $this->errorJson(1, '没有消息信息');
                exit;
            }
        }
    }

    /**
     * 账单明细
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function integrallist(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');
            $page = $request->post('page');


            if (empty($user_id)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            if (empty($page)) {
                $page = 1;
            }
            $pageSize = 10;

            $integralList = Db::table('mrs_integral_detail')
                ->where('user_id', '=', $user_id)
                ->field('integral_value,type,action_desc,create_time')
                ->order('create_time desc')
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select();

            $totalCount = Db::table('mrs_integral_detail')
                ->where('user_id', '=', $user_id)
                ->count();

            if ($integralList) {
                $total_page = ceil($totalCount / $pageSize);

                foreach ($integralList as $k => $v) {
                    $integralList[$k]['create_time'] = date('Y-m-d H:i', $v['create_time']);
                }
                $data['integralList'] = $integralList;
                $data['total_page'] = $total_page;
                echo $this->successJson($data);
                exit;
            } else {
                echo $this->errorJson(1, '没有账单信息');
                exit;
            }
        }
    }

    public function joincart(Request $request)
    {
        if ($request->isPost()) {
            $user_id = intval($request->post('user_id'));
            $goods_id = intval($request->post('goods_id'));
            $goods_num = intval($request->post('goods_num'));
            $goods_sku = $request->post('goods_sku');

            $goods_num = empty($goods_num) ? 1 : $goods_num;
            if (empty($user_id)) {
                echo $this->errorJson(1, '缺少关键数据user_id');
                exit;
            }
            if (empty($goods_id)) {
                echo $this->errorJson(1, '缺少关键数据goods_id');
                exit;
            }
            if (empty($goods_sku)) {
                echo $this->errorJson(1, '缺少关键数据goods_sku');
                exit;
            }

            $goods = Db::table('mrs_goods')->where(array('goods_id' => $goods_id))->find();

            if (empty($goods)) {
                echo $this->errorJson(1, '找不到对应商品信息');
                exit;
            }

            $where = array();
            $where[] = ['user_id', '=', $user_id];
            $where[] = ['goods_id', '=', $goods_id];
            $where[] = ['sku_json', '=', json_encode($goods_sku)];

            $cart = Db::table('mrs_carts')->where($where)->find();
            //判断是否已存在
            if (!empty($cart)) {
                $sql = "update mrs_carts set goods_num=goods_num+{$goods_num} where cart_id={$cart['cart_id']}";
                $cart = Db::table('mrs_carts')->execute($sql);
            } else {
                $cartData = array();
                $cartData['user_id'] = $user_id;
                $cartData['goods_id'] = $goods['goods_id'];
                $cartData['goods_name'] = $goods['goods_name'];
                $cartData['goods_image'] = $goods['goods_img'];
                $cartData['goods_price'] = $goods['goods_price'];
                $cartData['sku_json'] = json_encode($goods_sku);
                $cartData['is_check'] = '1';
                $cartData['goods_num'] = $goods_num;
                $cartData['create_time'] = time();

                $cart_id = Db::table('mrs_carts')->insert($cartData);

            }
            echo $this->successJson();
            exit;
        }
    }

    /**
     * @param Request $request
     * @searchkey 搜索地址
     */
    public function getpostcode(Request $request)
    {
        $searchkey = $request->get('searchkey');

        $url = 'http://cpdc.chinapost.com.cn/web/index.php?m=postsearch&c=index&a=ajax_addr&searchkey=' . $searchkey;

        $result = doPostHttp($url, '');
        echo $result;
        exit;
    }

    public function sendvcode(Request $request)
    {
        $phone = $request->post("phone");
        if (!preg_match("/^1[3456789]{1}\d{9}$/", $phone)) {
            echo errorJson('1', '请输入正确的电话');
            exit;
        }

        $tpl_code = 'sms_vcode';
        echo sendSms($phone, 'sms_vcode');

        return;
    }

    //用户登录（短信或验证码）
    public function userlogin(Request $request)
    {
        $phone = $request->post("phone");
        $user_id = $request->post("user_id");


        $login_type = $request->post("login_type");
        $vcode = $request->post("vcode");
        $password = $request->post("password");

        if ($login_type == '1' && !preg_match("/^1[3456789]{1}\d{9}$/", $phone)) {
            echo errorJson('1', '请输入正确的电话');
            exit;
        }
//        $phone = '18578303396';
//        $login_type = '2';
//        $password = '123456';

        if ($login_type == 1 && empty($vcode)) {
            echo errorJson('1', '请输入验证码');
            exit;
        } elseif ($login_type == 2 && empty($password)) {
            echo errorJson('1', '请输入密码');
            exit;
        }

        //验证码登录
        if ($login_type == 1) {
            $smsRecord = Db::table("mrs_sms_record")->where(array('phone' => $phone, 'code' => $vcode))->order('record_time desc')->find();
            if (empty($smsRecord)) {
                echo errorJson('1', '验证码不正确');
                exit;
            } else if ($smsRecord['is_use'] == 1) {
                echo errorJson('1', '验证码已失效');
                exit;
            } else if ($smsRecord['valid_date'] < time()) {
                echo errorJson('1', '验证码已失效');
                exit;
            }

            $user = Db::table('mrs_user')->where(array('phone_no' => $phone))->find();
            if (empty($user)) {
                $userData = array();
                $userData['phone_no'] = $phone;
                $userData['status'] = 1;
                $userData['create_time'] = time();
                Db::table('mrs_user')->insert($userData);
                $user_id = Db::table("mrs_user")->getLastInsID();
                $userData['user_id'] = $user_id;

                $user = $userData;
            } else {
                if ($user['status'] == '2') {
                    echo errorJson('1', '该用户已被禁用');
                    exit;
                }
                unset($user['password']);
            }

            //使验证码失效
            Db::table("mrs_sms_record")->where('record_id', '=', $smsRecord['record_id'])->update(array('is_use' => 1));

            $data = array();
            $data['userInfo'] = $user;

            echo $this->successJson($data);
            exit;
        } else {
            //密码登录
            $user = Db::table('mrs_user')->where(array('phone_no' => $phone))->find();

            if (empty($user)) {
                echo errorJson('1', '用户名或密码错误');
                exit;
            }

            if ($user['status'] == '2') {
                echo errorJson('1', '该用户已被禁用');
                exit;
            }
            $password = md5($user['user_id'] . md5($password));

            if ($password != $user['password']) {
                echo errorJson('1', '用户名或密码错误');
                exit;
            }

            unset($user['password']);
            $data = array();
            $data['userInfo'] = $user;

            echo $this->successJson($data);
            exit;
        }
    }

    /**
     * 获取用户信息
     * @param Request $request
     */
    public function getUserInfo(Request $request)
    {
        if ($request->isPost()) {
            $user_id = $request->post('user_id');

            if (empty($user_id)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            $user = Db::table('mrs_user')->where('user_id', '=', $user_id)->find();
            if (empty($user)) {
                echo $this->errorJson(1, '未找到用户信息');
                exit;
            } else {
                echo $this->successJson($user);
                exit;
            }
        }
    }

    public function checkvcode(Request $request)
    {
        $phone = $request->post('phone');
        $vcode = $request->post('vcode');

        if (empty($phone)) {
            echo errorJson('1', '请输入正确的手机号');
            exit;
        }

        if (empty($vcode)) {
            echo errorJson('1', '请输入验证码');
            exit;
        }


        $smsRecord = Db::table("mrs_sms_record")->where(array('phone' => $phone, 'code' => $vcode))->order('record_time desc')->find();
        if (empty($smsRecord)) {
            echo errorJson('1', '验证码不正确');
            exit;
        } else if ($smsRecord['is_use'] == 1) {
            echo errorJson('1', '验证码已失效');
            exit;
        } else if ($smsRecord['valid_date'] < time()) {
            echo errorJson('1', '验证码已失效');
            exit;
        }

        //使验证码失效
        Db::table("mrs_sms_record")->where('record_id', '=', $smsRecord['record_id'])->update(array('is_use' => 1));

        echo successJson();
        exit;
    }

    public function bindphone(Request $request)
    {
        $user_id = $request->post('user_id');
        $phone = $request->post('phone');

        if (empty($user_id)) {
            echo errorJson('1', '缺少关键参数user_id');
            exit;
        }
        if (empty($phone)) {
            echo errorJson('1', '缺少关键参数phone');
            exit;
        }

        if (!preg_match("/^1[3456789]{1}\d{9}$/", $phone)) {
            echo errorJson('1', '手机号码格式不正确');
            exit;
        }

        //绑定手机号
        Db::table('mrs_user')->where('user_id', '=', $user_id)->update(array('phone_no' => $phone));
        echo successJson();
        exit;
    }


    /**
     * 人脸登录
     * @param Request $request
     */
    public function facelogin(Request $request)
    {
        $image_path = $request->post("image_path");

        if (empty($image_path)) {
            echo errorJson('1', '缺少关键参数image_path');
            exit;
        }

        $userM = new \app\api\model\User();
        $res = $userM->searchFace($image_path);

        $result = json_decode($res, true);
        if ($result['error_code'] == 0) {
            $user_id = $result['result']['user_list'][0]['user_id'];
            $user = $userM->where('user_id', '=', $user_id)->find();
            if (empty($user)) {
                echo errorJson('1', '登录失败，不存在人脸对应用户。');
                exit;
            } else {
                unset($user['password']);
                $data = array();
                $data['userInfo'] = $user;
                echo successJson($data);
            }
        } else {
            echo errorJson('1', '登录失败，不存在人脸对应用户。');
            exit;
        }
    }

}
