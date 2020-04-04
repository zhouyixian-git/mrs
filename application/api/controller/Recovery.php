<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/12 0012
 * Time: 21:55
 */

namespace app\api\controller;


use think\Db;
use think\Request;

class Recovery extends Base
{

    public function nearsite(Request $request)
    {
        /*echo $this->getDistance('113.269068','23.119355','113.953166','22.549693',1, 2);
        exit;*/
        if ($request->isPost()) {
            $lng = $request->post('lng');
            $lat = $request->post('lat');
            $key_word = $request->post('key_word');
            $page = $request->post('page');

            if (empty($lng)) {
                echo $this->errorJson(1, '缺少关键数据lng');
                exit;
            }
            if (empty($lat)) {
                echo $this->errorJson(1, '缺少关键数据lat');
                exit;
            }

            if (empty($page)) {
                $page = 1;
            }
            $pageSize = 10;

            $whereOr = [];
            if (!empty($key_word)) {
                $whereOr = [
                    ['t1.site_name', 'like', "%$key_word%"],
                    ['t1.site_address', 'like', "%$key_word%"]
                ];
            }

            $siteList = Db::table('mrs_site')
                ->alias('t1')
                ->field("t1.site_id,t1.site_name,t1.lng,t1.lat,t1.site_address,
                    ROUND(
                        6378.138 * 2 * ASIN(
                            SQRT(
                                POW(
                                    SIN(
                                        (
                                            $lat * PI() / 180 - t1.lat * PI() / 180
                                        ) / 2
                                    ),
                                    2
                                ) + COS($lat * PI() / 180) * COS(t1.lat * PI() / 180) * POW(
                                    SIN(
                                        (
                                            $lng * PI() / 180 - t1.lng * PI() / 180
                                        ) / 2
                                    ),
                                    2
                                )
                            )
                        ) * 1000
                    ) AS distance
                ")
                ->whereOr($whereOr)
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->order('distance asc')
                ->select();

            $totalCount = Db::table('mrs_site')
                ->alias('t1')
                ->whereOr($whereOr)
                ->count();

            if ($siteList) {
                $total_page = ceil($totalCount / $pageSize);

                $data['siteList'] = $siteList;
                $data['total_page'] = $total_page;
                echo $this->successJson($data);
                exit;
            } else {
                echo $this->errorJson(1, '没有站点信息');
                exit;
            }
        }
    }



    /**
     * 计算两点地理坐标之间的距离
     * @param  Decimal $longitude1 起点经度
     * @param  Decimal $latitude1  起点纬度
     * @param  Decimal $longitude2 终点经度
     * @param  Decimal $latitude2  终点纬度
     * @param  Int     $unit       单位 1:米 2:公里
     * @param  Int     $decimal    精度 保留小数位数
     * @return Decimal
     */
    function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit=2, $decimal=2){

        $EARTH_RADIUS = 6370.996; // 地球半径系数
        $PI = 3.1415926;

        $radLat1 = $latitude1 * $PI / 180.0;
        $radLat2 = $latitude2 * $PI / 180.0;

        $radLng1 = $longitude1 * $PI / 180.0;
        $radLng2 = $longitude2 * $PI /180.0;

        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        $distance = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
        $distance = $distance * $EARTH_RADIUS * 1000;

        if($unit==2){
            $distance = $distance / 1000;
        }

        return round($distance, $decimal);

    }


    /**
     * 获取上门预约记录列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function callrecordlist(Request $request)
    {
        if ($request->isPost()) {
            $page = $request->post('page');
            $user_id = $request->post('user_id');

            if (empty($page)) {
                $page = 1;
            }
            $pageSize = 10;

            $order = 'create_time desc';

            $recordList = Db::table('mrs_call_recovery_record')
                ->where('user_id', '=', $user_id)
                ->order($order)
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->select();

            $totalCount = Db::table('mrs_call_recovery_record')
                ->where('user_id', '=', $user_id)
                ->count();

            if ($recordList) {
                $total_page = ceil($totalCount / $pageSize);

                foreach ($recordList as $k => $v) {
                    $recordList[$k]['call_create_time'] = empty($v['call_create_time'])?'-':date('Y-m-d H:i:s', $v['call_create_time']);
                }

                $data['record_list'] = $recordList;
                $data['total_page'] = $total_page;
                echo $this->successJson($data);
                exit;
            } else {
                echo $this->errorJson(1, '没有上门记录信息');
                exit;
            }
        }
    }

    /**
     * 生成上门预约记录
     * @param Request $request
     */
    public function callrecovery(Request $request){
        $user_id = $request->post("user_id");
        $address = $request->post("address");
        $lng = $request->post("lng");
        $lat = $request->post("lat");
        $user_phone_no = $request->post("user_phone_no");
        $recovery_cate_id = $request->post("recovery_cate_id");
        $recovery_cate_name = $request->post("recovery_cate_name");
        $call_create_time = ($request->post("call_create_time"));
        $remark = $request->post("remark");

        if(empty($user_id)){
            echo $this->errorJson('1','缺少关键参数user_id');
            exit;
        }

        if(empty($address)){
            echo $this->errorJson('1','缺少关键参数address');
            exit;
        }

        if(empty($lng)){
            echo $this->errorJson('1','缺少关键参数$lng');
            exit;
        }

        if(empty($lat)){
            echo $this->errorJson('1','缺少关键参数$lat');
            exit;
        }

        if(empty($user_phone_no)){
            echo $this->errorJson('1','缺少关键参数$user_phone_no');
            exit;
        }

        if(empty($recovery_cate_id)){
            echo $this->errorJson('1','缺少关键参数$recovery_cate_id');
            exit;
        }

        if(empty($recovery_cate_name)){
            echo $this->errorJson('1','缺少关键参数$recovery_cate_name');
            exit;
        }

        if(empty($call_create_time)){
            echo $this->errorJson('1','$call_create_time格式不正确');
            exit;
        }

        //查找最近的一个师傅
        $maxDistance = Db::table("mrs_system_setting")->where('setting_code', '=','call_recovery_max_distance')->find();

        $where = array();
        $where[] = ['is_actived', '=', 1];
        $master = Db::table('mrs_call_recovery_master')
            ->alias('t1')
            ->where($where)
            ->field("t1.master_id,t1.master_name,t1.lng,t1.lat,t1.address,t1.master_phone_no,
                    ROUND(
                        6378.138 * 2 * ASIN(
                            SQRT(
                                POW(
                                    SIN(
                                        (
                                            $lat * PI() / 180 - t1.lat * PI() / 180
                                        ) / 2
                                    ),
                                    2
                                ) + COS($lat * PI() / 180) * COS(t1.lat * PI() / 180) * POW(
                                    SIN(
                                        (
                                            $lng * PI() / 180 - t1.lng * PI() / 180
                                        ) / 2
                                    ),
                                    2
                                )
                            )
                        ) * 1000
                    ) AS distance
                ")
            ->order('distance asc')
            ->find();


        if($master['distance'] > $maxDistance['setting_value']){
            echo $this->errorJson('1', '附近没有可预约的上门人员.');
            exit;
        }

        if(empty($master)){
            echo $this->errorJson('1', '附近没有可预约的上门人员.');
            exit;
        }

        //生成记录
        $callRecord = array();
        $callRecord['master_id'] = $master['master_id'];
        $callRecord['master_name'] = $master['master_name'];
        $callRecord['master_phone_no'] = $master['master_phone_no'];
        $callRecord['user_id'] = $user_id;
        $callRecord['user_phone_no'] = $user_phone_no;
        $callRecord['address'] = $address;
        $callRecord['lng'] = $lng;
        $callRecord['lat'] = $lat;
        $callRecord['call_create_time'] = strtotime($call_create_time);
        $callRecord['recovery_cate_id'] = $recovery_cate_id;
        $callRecord['recovery_cate_name'] = $recovery_cate_name;
        $callRecord['remark'] = $remark;
        $callRecord['create_time'] = time();
        $callRecord['notice_status'] = 0;

        $res = Db::table('mrs_call_recovery_record')->insert($callRecord);
        if(empty($res)){
            echo errorJson('1', '系统异常，请稍后再试');
            exit;
        }
        $record_id = Db::table('mrs_call_recovery_record')->getLastInsID();

        //通知上门师傅
        /* 短信模板内容：  尊敬的师傅： 您好，您有新的预约上门回收订单，地点为“{address}”,联系电话：{user_phone_no},预约上门时间：{call_create_time},备注：{remark},请您按时上门回收，谢谢。 */
        $patterns = array();
        $replacements = array();

        $patterns[] = '/{address}/';
        $patterns[] = '/{user_phone_no}/';
        $patterns[] = '/{call_create_time}/';
        $patterns[] = '/{remark}/';

        $replacements[] = $address;
        $replacements[] = $user_phone_no;
        $replacements[] = $call_create_time;
        $replacements[] = empty($remark)?'无':$remark;

        $smsParam = array();
        $smsParam['address'] = $address;
        $smsParam['user_phone_no'] = $user_phone_no;
        $smsParam['call_create_time'] = $call_create_time;
        $smsParam['remark'] = empty($remark)?'无':$remark;

        $res = sendSmsCommon($master['master_phone_no'], 'call_master_order', $patterns, $replacements,$smsParam);
        $result = json_decode($res, true);

        if($result['errcode'] == 1){
            Db::table('mrs_call_recovery_record')->where('call_recovery_record_id', '=', $record_id)->delete();

            echo errorJson('1', '预约失败，请联系站点工作人员');
            exit;
        }else{
            $data = array();
            $data['notice_status'] = 1;
            Db::table('mrs_call_recovery_record')->where('call_recovery_record_id', '=', $record_id)->update($data);

            echo successJson();
            exit;

        }
    }

    public function getcallcate(Request $request){

        $cate = new \app\api\model\Callrecoverycate();
        $cateData = $cate->getCateTree(0);


        echo successJson($cateData);
        exit;
    }
    public function createserialdata(Request $request){
        $mach_id = $request->post("mach_id");
        $order = $request->post("order");
        $weight = $request->post("weight");
        $temp = $request->post("temp");
        $card_id = $request->post("card_id");

//        if(empty($mach_id)){
//            echo errorJson('1', '缺少关键参数mach_id');
//            exit;
//        }

//        aabb00000000000001fa00000000000000000000000001faccdd
        $retData = '';
        $mach_id = str2Hex($mach_id);
//        $mach_id = str2Hex($mach_id);
        $order = str2Hex($order, 4);
        $weight = str2Hex($weight);
        $temp = str2Hex($temp);
        $card_id = str2Hex($card_id);

        $retData = $mach_id;
        $retData .= $order.'0000';
        $retData .= $weight;
        $retData .= $temp;
        $retData .= $card_id;

        $crc = calcCRC($retData);
        $retData .= $crc;

        $retData = 'aabb'.$retData.'ccdd';

        recordLog('$retData-->'.$retData,'createserialdata.txt');
        echo successJson($retData);
        exit;
    }


    public function analyserial(Request $request){
        $serial_data = $request->post("serial_data");
        recordLog('serial_data-->'.$serial_data,'analyserialData.txt');

//        $serial_data = bin2Bin($serial_data);
        //报文校验
        //获取各传感器数据

        //报文内容
        #主设备ID

        $data = array();
        $mach_id = reverseStr(substr($serial_data, 0, 8));
        $order = reverseStr(substr($serial_data, 8, 4));
        $senser = reverseStr(substr($serial_data, 12, 4));
        $weight = reverseStr(substr($serial_data, 16, 8));
        $temp = reverseStr(substr($serial_data, 24, 8));
        $card = reverseStr(substr($serial_data, 32, 8));
        $crc = reverseStr(substr($serial_data, 40, 4));

        $new_serial_data = $mach_id.$order.$senser.$weight.$temp.$card.$crc;
        recordLog('$new_serial_data-->'.$new_serial_data,'analyserialData.txt');


        $data['mach_id'] = hexdec('0x'.$mach_id);
        $data['order'] = hexdec('0x'.$order);
        $data['weight'] = hexdec('0x'.$weight);
        $data['temp'] = hexdec('0x'.$temp);
        $data['card'] = hexdec('0x'.$card);

        $sysCrc = calcCRC($new_serial_data);

//        recordLog('$sysCrc-->'.$sysCrc,'analyserialData.txt');
        if(strtolower($crc) != strtolower($sysCrc)){
            echo errorJson('1', '报文校验失败，请检查报文校验码');
            exit;
        }

        //传感器数据解析
        $binStr = decbin(hexdec('0x'.$senser));
        $binStr = substr($binStr,-8);
        $binStr = str_pad($binStr,8,"0",STR_PAD_LEFT);

        $senser_state = array();
        $senser_state['clip_guard'] = $binStr[0];
        $senser_state['open_putway'] = $binStr[1];
        $senser_state['close_putway'] = $binStr[2];
        $senser_state['open_maintain'] = $binStr[3];
        $senser_state['smoke'] = $binStr[7];
        if($binStr[4] || $binStr[5] || $binStr[6]){
            $senser_state['overflow'] = 1;
        }else{
            $senser_state['overflow'] = 0;
        }
        $data['senser_state'] = $senser_state;

        recordLog('$data-->'.json_encode($data),'analyserialData.txt');
        echo successJson($data);
        exit;
    }

    public function analymach(Request $request){
        $serial_data = $request->post("serial_data");
        recordLog('serial_data-->'.$serial_data,'analymach.txt');

//        $serial_data = bin2Bin($serial_data);
        //报文校验

        //报文内容
        #主设备ID
//            $serial_data = $matches[1];
        $main_mach_id = reverseStr(substr($serial_data, 0, 8));

//        echo $main_mach_id;exit;
        $serial_data = (substr($serial_data, 8));

        #子设备列表
        $i = 0;
        $machList = array();
        $domain = Config("domain");
        while (strlen($serial_data) >= 24) {
            $machData = (substr($serial_data, 0, 24));
            $serial_data = (substr($serial_data, 24));

            $machList[$i]['mach_id'] = hexdec('0x' .reverseStr(substr($machData, 0, 8)));
            $machList[$i]['total_wight'] = hexdec('0x' . reverseStr(substr($machData, 8, 8)));
            $senser = reverseStr(substr($machData, 16, 2));
            $machList[$i]['line_state'] = reverseStr(substr($machData, 18, 2));
            $cate_code = hexdec('0x' . reverseStr(substr($machData, 20, 4)));
            $machList[$i]['cate_code'] = $cate_code;

            //查询对应分类信息
            $recoveryCate = Db::table('mrs_recovery_cate')->where('cate_code', '=', $cate_code)->find();
            if (!empty($recoveryCate)) {
                $recoveryCate['bg_icon_img'] = $domain . $recoveryCate['bg_icon_img'];
                $machList[$i]['recoveryCate'] = $recoveryCate;
            }

            //传感器数据解析
            $binStr = decbin(hexdec('0x' . $senser));
            $binStr = str_pad($binStr, 8, "0", STR_PAD_LEFT);

            $senser_state = array();
            $senser_state['clip_guard'] = $binStr[0];
            $senser_state['open_putway'] = $binStr[1];
            $senser_state['close_putway'] = $binStr[2];
            $senser_state['open_maintain'] = $binStr[3];
            $senser_state['smoke'] = $binStr[7];
            if ($binStr[4] || $binStr[5] || $binStr[6]) {
                $senser_state['overflow'] = 1;
            } else {
                $senser_state['overflow'] = 0;
            }
            $machList[$i]['senser_state'] = $senser_state;

            $i++;
        }

        $crc = $serial_data;

        $data = array();
        $data['callback_type'] = '1';
        $data['machines'] = $machList;

        recordLog('retData-->' . json_encode($data), 'analymach.txt');

        echo successJson($data);
        exit;

    }


    public function recoveryaction(Request $request){
        $user_id = $request->post("user_id");
        $site_id = $request->post("site_id");
        $details = $request->post("details");

        if(empty($user_id)){
            echo errorJson('1','缺少关键参数$user_id');
            exit;
        }

        $user = Db::table('mrs_user')->where("user_id","=",$user_id)->find();
        if(empty($user)){
            echo errorJson('1','该用户不存在');
            exit;
        }

        if(empty($site_id)){
            echo errorJson('1','缺少关键参数$site_id');
            exit;
        }

        $site = Db::table('mrs_site')->where("site_id","=",$site_id)->find();
        if(empty($site)){
            echo errorJson('1','站点信息不存在');
            exit;
        }

        if(empty($details)){
            echo errorJson('1','缺少关键参数$details');
            exit;
        }

        //生成回收记录
        $details = json_decode($details, true);
        $recordDetails = array();
        $record = array();
        $record['recovery_record_sn'] = date("YmdHis", time()).rand(0000000,9999999);
        $record['site_id'] = $site["site_id"];
        $record['user_id'] = $user["user_id"];
        $record['site_name'] = $site["site_name"];
        $record['region_id'] = $site["region_id"];
        $record['region_name'] = $site["region_name"];
        $record['recovery_time'] = time();
        $record['total_integral'] =0;
        $record['total_weight'] =0;

        Db::startTrans();
        $res1 = Db::table('mrs_recovery_record')->insert($record);
        $record_id = Db::table('mrs_recovery_record')->getLastInsID();

        $total_integral = 0;
        $total_weight = 0;

        if(is_array($details) && count($details) > 0){
            foreach ($details as $k=>$v){
                $cate = Db::table("mrs_recovery_cate")->where("cate_id","=",$v['cate_id'])->find();
                if(empty($cate)){
                    echo errorJson('1','分类信息错误，无法生成回收记录');
                    exit;
                }
                //计算积分值
                $integral = $cate['integral'] * $v['weight']/$cate['unit_weight'];

                //计算总重量、总积分
                $total_integral += $integral;
                $total_weight += $v['weight'];

                $recordDetails[$k]['recovery_record_id'] = $record_id;
                $recordDetails[$k]['recovery_cate_id'] = $cate['cate_id'];
                $recordDetails[$k]['recovery_cate_name'] = $cate['cate_name'];
                $recordDetails[$k]['price'] = $cate['integral'];
                $recordDetails[$k]['weight'] = $v['weight'];
                $recordDetails[$k]['integral'] = $integral;
            }

            $data = array();
            $data['total_integral'] = $total_integral;
            $data['total_weight'] = $total_weight;
            if(empty($total_weight)){
                echo errorJson('1', '请确认有投放物品后再点击投放完成');
                exit;
            }
            $res2 = Db::table('mrs_recovery_record')->where("recovery_record_id","=",$record_id)->update($data);

            $res3 = Db::table("mrs_recovery_record_detail")->insertAll($recordDetails);

            //对应用户增加积分
            $userData= array();
            $userData['total_integral'] = $user['total_integral'] + $total_integral;
            $userData['able_integral'] = $user['able_integral'] + $total_integral;
            $userData['deliver_num'] = $user['deliver_num'] + 1;
            Db::table('mrs_user')->where('user_id','=',$user_id)->update($userData);

            //增加对应用户积分明细
            $integralDetail = array();
            $integralDetail['user_id'] = $user['user_id'];
            $integralDetail['integral_value'] = $total_integral;
            $integralDetail['type'] = 1;
            $integralDetail['action_desc'] = '回收物品增加积分';
            $integralDetail['invalid_time'] = time() + 86400*180;
            $integralDetail['create_time'] = time();
            $res4 = Db::table('mrs_integral_detail')->insert($integralDetail);


            if($res1 && $res2 && $res3 && $res4){
                Db::commit();
                echo successJson();
            }else{
                Db::rollback();
                echo errorJson('1', '系统异常，请稍后再试。');
            }
            exit;

        }else{
            echo errorJson('1','数据缺失，无法生成回收记录');
            exit;
        }

    }


    public function updatemachstate(Request $request){
        if($request->isPost()){
            $data = array();
            $data['mach_id'] = $request->post('mach_id');
            $data['site_id'] = $request->post('site_id');
            $data['weight'] = $request->post('weight');
            $data['temp'] = $request->post('temp');
            $data['clip_guard'] = $request->post('clip_guard');
            $data['open_putway'] = $request->post('open_putway');
            $data['open_maintain'] = $request->post('open_maintain');
            $data['smoke'] = $request->post('smoke');
            $data['overflow'] = $request->post('overflow');

            if(empty($data['mach_id'])){
                echo errorJson('1', '缺少关键参数mach_id');
                exit;
            }
            if(empty($data['site_id'])){
                echo errorJson('1', '缺少关键参数site_id');
                exit;
            }

            $site = Db::table('mrs_site')->where('site_id','=',$data['site_id'])->find();
            if(empty($site)){
                echo errorJson('1', '站点不存在');
                exit;
            }
            $data['site_name'] = $site['site_name'];
            $data['last_update_time'] = time();

            if($data['overflow'] == '1'){
                $data['is_warning'] = 1;

                //告警通知
                $setting = Db::table('mrs_system_setting')->where('setting_code','=','warning_notice_phone')->find();
                if(!empty($setting['setting_value'])){

                    //通知上门师傅
                    /* 短信模板内容：  尊敬的师傅： 您好，您有新的预约上门回收订单，地点为“{address}”,联系电话：{user_phone_no},预约上门时间：{call_create_time},备注：{remark},请您按时上门回收，谢谢。 */
                    $patterns = array();
                    $replacements = array();

                    $patterns[] = '/{s}/';
                    $patterns[] = '/{mach}/';
                    $patterns[] = '/{content}/';

                    $replacements[] = $site['site_name'];
                    $replacements[] = $data['mach_id'];
                    $replacements[] = '设备溢满告警，请及时处理';

                    $smsParam = array();
                    $smsParam['s'] = $site['site_name'];
                    $smsParam['mach'] = $data['mach_id'];
                    $smsParam['content'] = '设备溢满告警，请及时处理';

                    sendSmsCommon($setting['setting_value'], 'warning_notice', $patterns, $replacements,$smsParam);
                }
            }else{
                $data['is_warning'] = 0;
            }

            $where =array();
            $where[] = ['site_id', '=', $data['site_id']];
            $where[] = ['mach_id', '=', $data['mach_id']];

            $detail = Db::table('mrs_device_state_detail')->where($where)->find();

            if(empty($detail)){
                Db::table("mrs_device_state_detail")->insert($data);
            }else{
                unset($data['site_id']);
                unset($data['mach_id']);
                Db::table("mrs_device_state_detail")->where($where)->update($data);
            }


            echo successJson();
            exit;

        }
    }

    public function queryrecoveryrecord(Request $request){
        $user_id = $request->post('user_id');
        $page = $request->post('page');

        if(empty($user_id)){
            echo $this->errorJson('1', '缺少关键参数user_id');
            exit;
        }


        if (empty($page)) {
            $page = 1;
        }
        $pageSize = 10;

        $order = 'recovery_time desc';

        $recordList = Db::table('mrs_recovery_record')
            ->where('user_id', '=', $user_id)
            ->order($order)
            ->limit(($page - 1) * $pageSize, $pageSize)
            ->select();

        $totalCount = Db::table('mrs_recovery_record')
            ->where('user_id', '=', $user_id)
            ->count();

        if(is_array($recordList) && count($recordList) > 0){
            $total_page = ceil($totalCount / $pageSize);
            foreach ($recordList as $k=>$v){
                $recordList[$k]['recovery_time'] = date('Y-m-d_H:i:s', $v['recovery_time']);

                $details = Db::table('mrs_recovery_record_detail')->where('recovery_record_id','=',$v['recovery_record_id'])->select();
                $recordList[$k]['details'] = $details;
            }

            $data = array();
            $data['recordList'] = $recordList;
            $data['total_page'] = $total_page;

            echo $this->successJson($data);
        }else{
            echo $this->successJson(array());
            exit;
        }
    }





}
