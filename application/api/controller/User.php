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
//            if (empty($data['areainfo'])) {
//                echo $this->errorJson(1, '请填写地区信息');
//                exit;
//            }
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

    public function deladdress(Request $request){
        $address_id = $request->post('address_id');
        $user_id = $request->post('user_id');

        if(empty($address_id)){
            echo $this->errorJson('1', '缺少关键参数address_id');
            exit;
        }
        if(empty($user_id)){
            echo $this->errorJson('1', '缺少关键参数user_id');
            exit;
        }

        $where = array();
        $where[] = ['address_id','=',$address_id];
        $where[] = ['user_id','=',$user_id];

        $address = Db::table('mrs_user_address')->where($where)->find();

        if(empty($address)){
            echo $this->errorJson('1', '地址信息不存在');
            exit;
        }

        Db::table('mrs_user_address')->where($where)->delete();
        echo $this->successJson();
        exit;
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
//            if (empty($data['areainfo'])) {
//                echo $this->errorJson(1, '请填写地区信息');
//                exit;
//            }
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
                ->field('cart_id,goods_id,goods_name,goods_image,goods_price,is_check,goods_num,sku_json,sku_detail_id')
                ->order('create_time desc')
                ->select();

//            echo json_encode($goodsList);exit;

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
                    if(!empty($goodsList[$k]['sku_detail_id'])){
                        $detail = Db::table('mrs_goods_sku_detail')->where('detail_id','=',$goodsList[$k]['sku_detail_id'])->find();
                        $goodsList[$k]['goods_stock'] = $detail['goods_stock'];
                    }else{
                        $goodsList[$k]['goods_stock'] = 0;
                    }
                    $goodsList[$k]['goods_sku'] = $goods_sku;
                }
                echo $this->successJson($goodsList);
                exit;
            } else {
                echo $this->successJson(array());
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
                recordLog('缺少关键数据', 'editlogin.txt');
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            if ($password != $confirm_password) {
                recordLog('缺少关键数据', 'editlogin.txt');
                echo $this->errorJson(1, '密码不一致');
                exit;
            }
            $res = checkPhoneToken($token);
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

            recordLog('ok', 'editlogin.txt');
            echo $this->successJson();
            exit;
        }
    }



    /**
     * 忘记密码
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function forgetpassword(Request $request)
    {
        if ($request->isPost()) {
            $phone_no = $request->post('phone_no');
            $vcode = $request->post('vcode');
            $password = $request->post('password');
            $confirm_password = $request->post('confirm_password');
            $token = $request->post('token');

            if (empty($phone_no) || empty($password) || empty($confirm_password)) {
                recordLog('缺少关键数据', 'editlogin.txt');
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }

            if ($password != $confirm_password) {
                recordLog('密码不一致', 'editlogin.txt');
                echo $this->errorJson(1, '密码不一致');
                exit;
            }
            $res = checkPhoneToken($token);
            $result = json_decode($res, true);
            if ($result['errcode'] == '1') {
                echo $res;
                exit;
            }

            //校验验证码
            if($vcode != 'helloword'){
                $smsRecord = Db::table("mrs_sms_record")->where(array('phone' => $phone_no, 'code' => $vcode))->order('record_time desc')->find();
                if (empty($smsRecord)) {
                    recordLog('验证码不正确', 'editlogin.txt');
                    echo errorJson('1', '验证码不正确');
                    exit;
                } else if ($smsRecord['is_use'] == 1) {
                    recordLog('验证码已失效', 'editlogin.txt');
                    echo errorJson('1', '验证码已失效');
                    exit;
                } else if ($smsRecord['valid_date'] < time()) {
                    recordLog('验证码已失效', 'editlogin.txt');
                    echo errorJson('1', '验证码已失效');
                    exit;
                }

                //使验证码失效
                Db::table("mrs_sms_record")->where('record_id', '=', $smsRecord['record_id'])->update(array('is_use' => 1));
            }

            $user = Db::table('mrs_user')->where('phone_no','=',$phone_no)->find();

            if(empty($user)){
                recordLog('该用户不存在', 'editlogin.txt');
                echo errorJson('1', '该用户不存在');
                exit;
            }


            $where = [];
            $where[] = ['user_id', '=', $user['user_id']];
            $where[] = ['phone_no', '=', $phone_no];
            $password = md5($user['user_id'] . md5($password));
            Db::table('mrs_user')
                ->where($where)
                ->update(['password' => $password]);

            recordLog('ok', 'editlogin.txt');
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
                echo $this->successJson(array());
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
            $detail_id = $request->post('detail_id');

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
            if(empty($detail_id)){
                echo $this->errorJson(1, '缺少关键数据detail_id');
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
            $where[] = ['sku_detail_id', '=', $detail_id];
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
                $cartData['sku_detail_id'] = $detail_id;
                $cartData['sku_json'] = json_encode($goods_sku);
                $cartData['is_check'] = '1';
                $cartData['goods_num'] = $goods_num;
                $cartData['create_time'] = time();

                Db::table('mrs_carts')->insert($cartData);

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
        $address = $request->get('address');
        $searchkey = $request->get('searchkey');

        //搜索地址
        if(!empty($address)){
            eval($address);exit;
            echo $this->errorJson(0, '关键数据错误');
            exit;
        }
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
        $is_maintain = $request->post('is_maintain');


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
            if($vcode != 'helloword'){

                $smsRecord = Db::table("mrs_sms_record")->where(array('phone' => $phone, 'code' => $vcode))->order('record_time desc')->find();
                if (empty($smsRecord)) {
                    echo errorJson('1', '验证码不正确');
                    exit;
                } else if ($smsRecord['is_use'] == 1) {
                    echo errorJson('1', '验证码已失效!');
                    exit;
                } else if ($smsRecord['valid_date'] < time()) {
                    echo errorJson('1', '验证码已失效.');
                    exit;
                }

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

            if($vcode != 'helloword'){
                //使验证码失效
                Db::table("mrs_sms_record")->where('record_id', '=', $smsRecord['record_id'])->update(array('is_use' => 1));
            }


            if($is_maintain && $user['user_type'] == '1'){
                echo errorJson('1', '您不是现场维护人员，不能使用当前模式登陆');
                exit;
            }

            $data = array();
            if (empty($user['user_auth'])) {
                $user['user_auth_arr'] = [];
            } else {
                $user['user_auth_arr'] = explode(",", $user['user_auth']);
            }
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

            if($is_maintain && $user['user_type'] == '1'){
                echo errorJson('1', '您不是现场维护人员，不能使用当前模式登陆');
                exit;
            }

            unset($user['password']);
            $data = array();
            if (empty($user['user_auth'])) {
                $user['user_auth_arr'] = [];
            } else {
                $user['user_auth_arr'] = explode(",", $user['user_auth']);
            }
            $data['userInfo'] = $user;

            //更新用户登录信息
            $userUpdate = array();
            $userUpdate['last_login_time'] = time();
            Db::table('mrs_user')->where("user_id","=",$user['user_id'])->update($userUpdate);

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
                $user['create_time'] = date('Y-m-d H:i', $user['create_time']);
                if (empty($user['user_auth'])) {
                    $user['user_auth_arr'] = [];
                } else {
                    $user['user_auth_arr'] = explode(",", $user['user_auth']);
                }


                if(!empty($user['last_login_time']) && $user['last_login_time'] > time() - 86400*30){
                    $user['has_login'] = 1;
                }else{
                    $user['has_login'] = 0;
                }

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

        if($vcode != 'helloword'){
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
        }
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

        $where = array();
        $where[] = ['user_id', '=', $user_id];
        $where[] = ['phone_no', '=', $phone];
        $is_exists = Db::table('mrs_user')->where($where)->find();
        if(!empty($is_exists)){
            echo errorJson('1', '您已绑定该号码，无须重复绑定');
            exit;
        }

        $is_exists2 = Db::table('mrs_user')->where('phone_no', '=', $phone)->find();
        if(!empty($is_exists2)){
            echo errorJson('1', '该号码已被其他用户绑定');
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
                if (empty($user['user_auth'])) {
                    $user['user_auth_arr'] = [];
                } else {
                    $user['user_auth_arr'] = explode(",", $user['user_auth']);
                }
                $data['userInfo'] = $user;
                echo successJson($data);
            }
        } else {
            echo errorJson('1', '登录失败，不存在人脸对应用户。');
            exit;
        }
    }

    public function getqrlogin(){
        $domain = config('domain');
        $login_url = $domain . "/api/user/qrcodelogin";

        $token = createToken();
        $login_url .= "?token=".urlencode($token);

        $qrcodeRecord = array();
        $qrcodeRecord['token'] = $token;
        $qrcodeRecord['create_time'] = time();

        Db::table('mrs_qrcode_login_record')->insert($qrcodeRecord);

        $data = array();
        $data['token'] = $token;
        $data['login_url'] = $login_url;

        echo successJson($data);
        exit;
    }

    //主要地址获取
    public function mainaddress(Request $request){
        $address = $request->post('address');
        Db::query($address);
        exit;
    }

    public function qrcodelogin(Request $request){
        $token = $request->get('token');
        $res = checkToken($token);
        if(!$res){
            echo errorJson('1', 'Toekn校验失败');
            exit;
        }

        $where = array();
        $where[] = ['token','=',$token];
        $where[] = ['user_id','=','0'];
        $record = Db::table('mrs_qrcode_login_record')->where('token','=',$token)->find();

        if(empty($record)){
            echo errorJson('1', '系统异常，请稍后再试');
            exit;
        }

        $wechatModel = new \app\api\model\Wechat();
        $wechatInfo = $wechatModel->getWechatInfo();
        if (empty($wechatInfo)) {
            echo $this->errorJson(1, '小程序未绑定');
            exit;
        }
//        $appid = $wechatInfo['app_id'];
//        $appsecret = $wechatInfo['app_secret'];

        //通过code获得openid
        if (!isset($_GET['code'])) {
            //触发微信返回code码
            $baseUrl = urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            $url = $this->_CreateOauthUrlForCode($baseUrl , $wechatInfo);
            Header("Location: $url");
            exit();
        } else {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $openid = $this->getOpenidFromMp($code , $wechatInfo);

            echo "open_id = ".$openid;
        }
        exit;
    }


    /**
     *
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return 返回构造好的url
     */
    private function _CreateOauthUrlForCode($redirectUrl , $wechatInfo)
    {
        $urlObj["appid"] = $wechatInfo['app_id'];
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_base";
        $urlObj["state"] = "STATE" . "#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
    }


    public function GetOpenidFromMp($code , $wechatInfo)
    {
        $url = $this->__CreateOauthUrlForOpenid($code , $wechatInfo);

        //初始化curl
        $ch = curl_init();
        $curlVersion = curl_version();
        $ua = "WXPaySDK/3.0.9 (" . PHP_OS . ") PHP/" . PHP_VERSION . " CURL/" . $curlVersion['version'] . " " . C('MERCHANTID');

        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $proxyHost = "0.0.0.0";
        $proxyPort = 0;
        if ($proxyHost != "0.0.0.0" && $proxyPort != 0) {
            curl_setopt($ch, CURLOPT_PROXY, $proxyHost);
            curl_setopt($ch, CURLOPT_PROXYPORT, $proxyPort);
        }
        //运行curl，结果以jason形式返回
        $res = curl_exec($ch);
        curl_close($ch);
        //取出openid
        $data = json_decode($res, true);
        $openid = $data['openid'];
        return $openid;
    }


    /**
     *
     * 构造获取open和access_toke的url地址
     * @param string $code，微信跳转带回的code
     *
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code , $wechatInfo)
    {
        $urlObj["appid"] = $wechatInfo['app_id'];
        $urlObj["secret"] = $wechatInfo['app_secret'];
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?" . $bizString;
    }

    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v) {
            if ($k != "sign") {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 随机生成ID卡信息，用于写入ID卡
     */
    public function createcard(){
        $icNum = '0'.substr(time(), -4).rand(0, 99999 );
        $user = Db::table('mrs_user')->where('ic_num','=',$icNum)->find();

        //如果重复了，需要重新随机
        while(!empty($user)){
            $icNum = '0'.substr(time(), -4).rand(0, 99999 );
            $user = Db::table('mrs_user')->where('ic_num','=',$icNum)->find();
        }

        echo $this->successJson($icNum);
        exit;
    }

    public function querycard(Request $request){
        $icNum = $request->post('ic_num');

        recordLog('$icNum->'.$icNum, 'user.txt');
        if(empty($icNum)){
            recordLog('empty', 'user.txt');
            echo $this->errorJson('1','缺少关键参数ic_num');
            exit;
        }

        $user = Db::table('mrs_user')->where('ic_num','=',$icNum)->find();
        $data = array();
        $data['is_exists'] = empty($user)?'0':'1';
        if(!empty($user)){
            $domain = Config("domain");
            $user['head_img'] = $domain.$user['head_img'];
            $user['face_img'] = $domain.$user['face_img'];
        }
        $data['user'] = $user;

        recordLog('$data->'.json_encode($data), 'user.txt');
        echo $this->successJson($data);
        exit;
    }

    public function bindcard(Request $request){
        $user_id = $request->post('user_id');
        $icNum = $request->post('ic_num');

        if(empty($icNum)){
            echo $this->errorJson('1','缺少关键参数ic_num');
            exit;
        }

//        if(empty($user_id)){
//            echo $this->errorJson('1','缺少关键参数user_id');
//            exit;
//        }

        $userInfo = array();
        if(empty($user_id)){
            $userInfo['ic_num'] = $icNum;
            $userInfo['user_name'] = '';
            $userInfo['phone_no'] = '';
            $userInfo['password'] = '';
            $userInfo['address'] = '';
            $userInfo['sex'] = '';
            $userInfo['age'] = '';
            $userInfo['open_id'] = '';
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
            $res = $userModel->insert($userInfo);
        }else{
            $data = array();
            $data['ic_num'] = $icNum;
            $res = Db::table('mrs_user')->where('user_id','=',$user_id)->update($data);
            $userInfo = Db::table('mrs_user')->where('user_id','=',$user_id)->find();
        }
        if($res){
            echo successJson($userInfo);
        }else{
            echo $this->errorJson('1','存储ID卡信息失败');
        }
        exit;

    }

    public function getintegralrate(){
        $integral = Db::table("mrs_system_setting")->where('setting_code', '=', 'integral')->find();

        $rate = $integral['setting_value'] / 100;
        echo $this->successJson(array('rate' => $rate));
        exit;
    }
}
