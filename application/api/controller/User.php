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
                ->field('consignee,telephone,province_name,city_name,district_name,address,is_default')
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
            $data['province_name'] = $request->post('province_name');
            $data['city_name'] = $request->post('city_name');
            $data['district_name'] = $request->post('district_name');
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
            if (empty($data['province_name']) || empty($data['city_name']) || empty($data['district_name'])) {
                echo $this->errorJson(1, '请填写手机号码信息');
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
            $data['province_name'] = $request->post('province_name');
            $data['city_name'] = $request->post('city_name');
            $data['district_name'] = $request->post('district_name');
            $data['address'] = $request->post('address');
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
            if (empty($data['province_name']) || empty($data['city_name']) || empty($data['district_name'])) {
                echo $this->errorJson(1, '请填写手机号码信息');
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
                ->field('goods_id,goods_name,goods_image,goods_price,is_check,goods_num')
                ->order('create_time desc')
                ->select();

            if ($goodsList) {
                $domain = config('domain');
                foreach ($goodsList as $k => $v) {
                    $goodsList[$k]['goods_image'] = $domain . $v['goods_image'];
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
            $vcode = $request->post('vcode');

            if (empty($user_id) || empty($phone_no) || empty($password) || empty($confirm_password) || empty($vcode)) {
                echo $this->errorJson(1, '缺少关键数据');
                exit;
            }
            if ($password != $confirm_password) {
                echo $this->errorJson(1, '密码不一致');
                exit;
            }

            $where = [];
            $where[] = ['user_id', '=', $user_id];
            $where[] = ['phone_no', '=', $phone_no];
            $password = md5($phone_no . md5($password));
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
    public function msgdetail(Request $request){
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
    public function integrallist(Request $request){
        if($request->isPost()){
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
}
