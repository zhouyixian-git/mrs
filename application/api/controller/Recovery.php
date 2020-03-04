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
//        $where[] = ['distance', '<=', $maxDistance['setting_value']];
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

//        var_dump($master);
//        exit;
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

        if(empty($mach_id)){
            echo errorJson('1', '缺少关键参数mach_id');
            exit;
        }

//        aabb00000000000001fa00000000000000000000000001faccdd
        $retData = '';
        $mach_id = str2Hex(date("ym").$mach_id);
//        $mach_id = str2Hex($mach_id);
        $order = str2Hex($order);
        $weight = str2Hex($weight);
        $temp = str2Hex($temp);
        $card_id = str2Hex($card_id);

        $retData = $mach_id;
        $retData .= $order;
        $retData .= $weight;
        $retData .= $temp;
        $retData .= $card_id;

        $crc = calcCRC($retData);
        $retData .= $crc;

        $retData = 'aabb'.$retData.'ccdd';

        echo successJson($retData);
        exit;
    }

    public function analyserial(Request $request){
        $serial_data = $request->post("serial_data");

        //报文校验
        $preg = '/^ffee(.*?)ccdd$/i';
        preg_match($preg,$serial_data,$matches);
        if(empty($matches[1])){
            echo errorJson('1', '报文格式错误，无法解析。');
            exit;
        }

        //报文内容
        #主设备ID
        $serial_data = $matches[1];
        $main_mach_id = substr($serial_data, 0, 8);
        $serial_data = substr($serial_data, 8);

        #子设备列表
        $i = 0;
        $machList = array();
        while(strlen($serial_data) >= 24){
            $machData = substr($serial_data, 0, 24);
            $serial_data = substr($serial_data, 24);

            $machList[$i]['mach_id'] = substr($machData, 0, 8);
            $machList[$i]['total_wight'] = hexdec('0x'.substr($machData, 8, 8));
            $machList[$i]['mach_state'] = substr($machData, 16, 2);
            $machList[$i]['line_state'] = substr($machData, 18, 2);
            $machList[$i]['type'] = hexdec('0x'.substr($machData, 20, 4));

            $i++;
        }

        $data = array();
        $data['main_mach_id'] = $main_mach_id;
        $data['machines'] = $machList;

        echo successJson($data);
        exit;
    }




}
