<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * 记录日志
 * @param unknown_type $log_content 日志内容
 * @param unknown_type $log_file SEED_TEMP_ROOT+文件名
 */
function recordLog($log_content = 'test', $log_file = 'log.txt')
{
    $filename = '../public/log/' . $log_file;
    $content = "[" . date('Y-m-d H:i:s') . "]" . $log_content;
    if (file_exists($filename)) {
        $last_content = file_get_contents($filename);
        $content = $last_content . "\r\n" . $content;
    }
    file_put_contents($filename, $content);
}

/**
 * 发送验证码通用方法
 * @param $phone
 * @param $tpl_code
 * @return false|mixed|SimpleXMLElement|string|void
 */
function sendSms($phone, $tpl_code)
{

    $accessKeyId = '';
    $accessSecret = '';
    $sign = '';

    $smsApi = Db::table('mrs_api')->where(array('api_code' => 'aliyunsms', 'status' => '1'))->find();

    if (empty($smsApi)) {
        return errorJson('1', 'Not found the sms api!');
    }
    $smsTpl = Db::table('mrs_sms_tpl')->where(array('tpl_code' => $tpl_code))->find();
    if (empty($smsTpl)) {
        return errorJson('1', 'Not found the sms template!');
    }

    //生成验证码
    $code = rand(100000, 999999);

    //生成短信记录
    $patterns = array();
    $replacements = array();
    $patterns[] = '/{code}/';
    $replacements[] = $code;
    $content = preg_replace($patterns, $replacements, $smsTpl['tpl_content']);

    $smsRecordData = array();
    $smsRecordData['phone'] = $phone;
    $smsRecordData['code'] = $code;
    $smsRecordData['sms_content'] = $content;
    $smsRecordData['valid_date'] = time() + 10 * 60;
    $smsRecordData['is_use'] = '0';
    $smsRecordData['record_time'] = time();

    Db::table('mrs_sms_record')->insert($smsRecordData);

    $params = Db::table('mrs_api_params')->where(array('api_id' => $smsApi['api_id']))->select();
    if (is_array($params) && count($params)) {
        foreach ($params as $k => $param) {
            if ($param['param_code'] == 'AccessKeyID') {
                $accessKeyId = $param['param_value'];
            } else if ($param['param_code'] == 'AccessKeySecret') {
                $accessSecret = $param['param_value'];
            } else if ($param['param_code'] == 'sign') {
                $sign = $param['param_value'];
            }
        }
    }

    if (empty($accessKeyId) || empty($accessSecret) || empty($sign)) {
        return errorJson('1', '配置参数缺失!');
    }

    //引进阿里的配置文件
//    Vendor('api_sdk.vendor.autoload');
    require_once '../extend/api_sdk/vendor/autoload.php';
    // TP5.1及以上用require_once

    // 加载区域结点配置
    \Aliyun\Core\Config::load();
    $profile = \Aliyun\Core\Profile\DefaultProfile::getProfile('cn-hangzhou', $accessKeyId, $accessSecret);
    // 增加服务结点
    \Aliyun\Core\Profile\DefaultProfile::addEndpoint('cn-hangzhou', 'cn-hangzhou', 'Dysmsapi', 'dysmsapi.aliyuncs.com');
    // 初始化AcsClient用于发起请求
    $acsClient = new \Aliyun\Core\DefaultAcsClient($profile);
    // 初始化SendSmsRequest实例用于设置发送短信的参数
    $request = new \Aliyun\Api\Sms\Request\V20170525\SendSmsRequest();
    // 必填，设置雉短信接收号码
    $request->setPhoneNumbers($phone);
    // 必填，设置签名名称
    $request->setSignName($sign);
    // 必填，设置模板CODE
    $request->setTemplateCode($smsTpl['aliyun_code']);
    $params = array(
        'code' => $code,
    );
    // 可选，设置模板参数
    $request->setTemplateParam(json_encode($params));
    // 可选，设置流水号
    //if($outId) {
    //    $request->setOutId($outId);
    //}
    // 发起访问请求
    $acsResponse = $acsClient->getAcsResponse($request);
    // 打印请求结果

    return successJson();
}


/**
 * 发送短信通配方法|非验证码
 * @param $phone
 * @param $tpl_code
 * @param $patterns 正则数组
 * @param $replacements 替换数据数组
 * @return false|mixed|SimpleXMLElement|string|void
 *
 */
function sendSmsCommon($phone='', $tpl_code='',$patterns = array(),$replacements = array(), $smsParam= array())
{

    $accessKeyId = '';
    $accessSecret = '';
    $sign = '';

    $smsApi = Db::table('mrs_api')->where(array('api_code' => 'aliyunsms', 'status' => '1'))->find();

    if (empty($smsApi)) {
        return errorJson('1', 'Not found the sms api!');
    }
    $smsTpl = Db::table('mrs_sms_tpl')->where(array('tpl_code' => $tpl_code))->find();
    if (empty($smsTpl)) {
        return errorJson('1', 'Not found the sms template!');
    }

    //生成短信记录
    $content = preg_replace($patterns, $replacements, $smsTpl['tpl_content']);

    $smsRecordData = array();
    $smsRecordData['phone'] = $phone;
    $smsRecordData['sms_content'] = $content;
    $smsRecordData['valid_date'] = time() + 10 * 60;
    $smsRecordData['is_use'] = '0';
    $smsRecordData['record_time'] = time();

    Db::table('mrs_sms_record')->insert($smsRecordData);

    $params = Db::table('mrs_api_params')->where(array('api_id' => $smsApi['api_id']))->select();
    if (is_array($params) && count($params)) {
        foreach ($params as $k => $param) {
            if ($param['param_code'] == 'AccessKeyID') {
                $accessKeyId = $param['param_value'];
            } else if ($param['param_code'] == 'AccessKeySecret') {
                $accessSecret = $param['param_value'];
            } else if ($param['param_code'] == 'sign') {
                $sign = $param['param_value'];
            }
        }
    }

    if (empty($accessKeyId) || empty($accessSecret) || empty($sign)) {
        return errorJson('1', '配置参数缺失!');
    }

    //引进阿里的配置文件
//    Vendor('api_sdk.vendor.autoload');
    require_once '../extend/api_sdk/vendor/autoload.php';
    // TP5.1及以上用require_once

    // 加载区域结点配置
    \Aliyun\Core\Config::load();
    $profile = \Aliyun\Core\Profile\DefaultProfile::getProfile('cn-hangzhou', $accessKeyId, $accessSecret);
    // 增加服务结点
    \Aliyun\Core\Profile\DefaultProfile::addEndpoint('cn-hangzhou', 'cn-hangzhou', 'Dysmsapi', 'dysmsapi.aliyuncs.com');
    // 初始化AcsClient用于发起请求
    $acsClient = new \Aliyun\Core\DefaultAcsClient($profile);
    // 初始化SendSmsRequest实例用于设置发送短信的参数
    $request = new \Aliyun\Api\Sms\Request\V20170525\SendSmsRequest();
    // 必填，设置雉短信接收号码
    $request->setPhoneNumbers($phone);
    // 必填，设置签名名称
    $request->setSignName($sign);
    // 必填，设置模板CODE
    $request->setTemplateCode($smsTpl['aliyun_code']);
    // 可选，设置模板参数
    $request->setTemplateParam(json_encode($smsParam));
    // 可选，设置流水号
    //if($outId) {
    //    $request->setOutId($outId);
    //}
    // 发起访问请求
    $acsResponse = $acsClient->getAcsResponse($request);
    // 打印请求结果

    return successJson();

}


//function connectModbus(){
//
//    ini_set('memory_limit',"88M");//重置php可以使用的内存大小为64M
//    set_time_limit(0);
//    ob_implicit_flush(1);
//    include('../extend/Ohsce/loadohsce.php');
//
//    Ohsce_eng_serial_creat($hscecom,"com7"); //OHSCE会默认为你创建一个 9600,n,8,1 写读的串口资源
//    Ohsce_eng_serial_open($hscecom); //一旦通过该函数成功开启了串口，此串口就被OHSCE进程占用了 此时串口资源变为可用状态
//    Ohsce_eng_serial_write($hscecom,"01030001000415c9",true);//向串口设备发送数据 以16进制发送
//    Ohsce_eng_serial_read($hscecom,$data,null,true); // 读取串口数据 返回数据长度为未知 以16进制返回
//    echo $data; //输出数据
//    sleep(30);
//
//}


/**
 * 返回成功信息
 * @param null $data
 * @return false|string|void
 */
function successJson($data = null)
{
    $result = [];
    $result['errcode'] = 0;
    $result['msg'] = 'success';
    if (!empty($data)) {
        $result['data'] = $data;
    }
    return json_encode($result);
}

/**
 * 返回错误信息
 * @param int $errcode
 * @param string $message
 * @return false|string|void
 */
function errorJson($errcode = 1, $message = 'error')
{
    $result = [];
    $result['errcode'] = $errcode;
    $result['msg'] = $message;
    return json_encode($result);
}


/**提交POST的HTTP请求，并返回结果
 * @param $url
 * @param $post_string
 * @return mixed
 */
function doPostHttp($url, $post, $cookie = '', $cookiejar = '', $referer = '', $timeout = 60)
{
    $tmpInfo = '';
    $cookiepath = getcwd() . './' . $cookiejar;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
    if ($referer) {
        curl_setopt($curl, CURLOPT_REFERER, $referer);
    } else {
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    }
    if ($post) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }
    if ($cookie) {
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    }
    if ($cookiejar) {
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookiepath);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $cookiepath);
    }
    //允许跳转访问
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8","Content-Length:".strlen($post)));

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
//            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

    $tmpInfo = curl_exec($curl);
    if (curl_errno($curl)) {
        return curl_error($curl);
    }
    curl_close($curl);
    return $tmpInfo;
}

//发起POST请求
function httpPost($url, $data = '')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.89 Safari/537.36");
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

//发起POST请求
function httpPostCert($url, $data = '', $second = 30, $header = array())
{
    $ch = curl_init();
    //超时时间
    curl_setopt($ch, CURLOPT_TIMEOUT, $second);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //这里设置代理，如果有的话
    //curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
    //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    //默认格式为PEM，可以注释
    $cert_path = config('cert_path');
    curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');//绝对地址可使用 dirname(__DIR__)打印，如果不是绝对地址会报 58 错误

    curl_setopt($ch, CURLOPT_SSLCERT, $cert_path . 'apiclient_cert.pem');
    curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
    curl_setopt($ch, CURLOPT_SSLKEY, $cert_path . 'apiclient_key.pem');
    if (count($header) >= 1) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $data = curl_exec($ch);
    if ($data) {
        curl_close($ch);
        return $data;
    } else {
        $error = curl_errno($ch);
        echo "call faild, errorCode:$error\n";
        curl_close($ch);
        return false;
    }
}

//创建随机字符串
function createNoncestr($length = 32)
{
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

//生成签名
function sign($data, $mch_key)
{
    ksort($data);
    $str = toParamss($data);
    $str = $str . '&key=' . $mch_key;
    $str = md5($str);
    //将字符串转成大写
    $str = strtoupper($str);
    return $str;
}

//格式化参数成URL参数
function toParamss($data)
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
function xmlToArray($xml)
{
    $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $array_data;
}

//将数组转成xml
function arrayToXml($arr)
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

if (!function_exists('dateTime')) {
    function dateTime($time)
    {
        if (empty($time)) {
            return '';
        }
        $ChineseDate = date('Y-m-d H:i', $time);
        return $ChineseDate;
    }
}

function checkPhoneToken($token){
    $key = 'jiayuanpro123';
    $cryptType = 'des-ecb';
    $res = openssl_decrypt($token, $cryptType, $key);
    if($res){
        $result = explode("|",$res);

        if (!preg_match("/^1[3456789]{1}\d{9}$/", $result[0])) {
            echo errorJson('1', 'Token校验失败');
            exit;
        }

        if (time() > $result[1] + 3600 || $result[1]> time()) {
            echo errorJson('1', 'Token已失效');
            exit;
        }

        return successJson();
    }else{
        return errorJson('1', 'Token校验失败');
       exit;
    }
}


function createToken(){
    $key = 'jiayuanpro123';
    $cryptType = 'des-ecb';
    $str = rand(100000 , 999999);
    $str .= '|';
    $str .= time();

    $res = openssl_encrypt($str , $cryptType, $key);

    return $res;
}


function checkToken($token){
    $key = 'jiayuanpro123';
    $cryptType = 'des-ecb';
    $res = openssl_decrypt($token, $cryptType, $key);
    if($res){
        $result = explode("|",$res);

        if (time() > $result[1] + 3600 || $result[1]> time()) {
            echo errorJson('1', 'Token已失效');
            exit;
        }

        return true;
    }else{
        return false;
        exit;
    }
}

function wxDecrypt($encryptedData,$iv,$sessionKey){
    include_once "../extend/wx_extend/wxBizDataCrypt.php";

    $wechatModel = new \app\api\model\Wechat();
    $wechatInfo = $wechatModel->getWechatInfo();
    if (empty($wechatInfo)) {
        echo $this->errorJson(1, '小程序未绑定');
        exit;
    }
    $appid = $wechatInfo['app_id'];

    $pc = new WXBizDataCrypt($appid, $sessionKey);
    $errCode = $pc->decryptData($encryptedData, $iv, $data);

    if ($errCode == 0) {
        return successJson(json_decode($data, true));
    } else {
        echo errorJson('1', $errCode);
    }
    exit;
}

/**
 * 获取图片的Base64编码(不支持url)
 * @date 2020年2月1日15:43:40
 *
 * @param $img_file 传入本地图片地址
 *
 * @return string
 */
function imgToBase64($img_file) {

    $img_base64 = '';
    if (file_exists($img_file)) {
        $app_img_file = $img_file; // 图片路径
        $img_info = getimagesize($app_img_file); // 取得图片的大小，类型等

        //echo '<pre>' . print_r($img_info, true) . '</pre><br>';
        $fp = fopen($app_img_file, "r"); // 图片是否可读权限

        if ($fp) {
            $filesize = filesize($app_img_file);
            $content = fread($fp, $filesize);
            $file_content = chunk_split(base64_encode($content)); // base64编码
        }
        fclose($fp);
    }

    return $file_content; //返回图片的base64
}

/**
 * @param $str 报文内容，不包括包头包尾
 */
function calcCRC($str){
//    00000000000001fa000000000000000000000000
    $sendbuf['device'] = (hexdec('0x'.substr($str, 0, 8)));
    $sendbuf['order'] = (hexdec('0x'.substr($str, 8, 4)));
    $sendbuf['senser'] = (hexdec('0x'.substr($str, 12, 4)));
    $sendbuf['weight'] = (hexdec('0x'.substr($str, 16, 8)));
    $sendbuf['temp'] = (hexdec('0x'.substr($str, 24, 8)));
    $sendbuf['card_id'] = (hexdec('0x'.substr($str, 32, 8)));

    $crc = dechex($sendbuf['device'] ^ $sendbuf['order'] ^ $sendbuf['senser'] ^ $sendbuf['weight'] ^ $sendbuf['temp'] ^ $sendbuf['card_id'] );
    $crc = substr($crc, -4);
    return $crc = str_pad($crc,4,"0",STR_PAD_LEFT);
}

/**
 * @param $str 报文内容，不包括包头包尾
 */
function calcCRC2($str){
//    ffee000010010000000100007A63010104570000000200000063010104580000000300007A63010104590000000400007A630101045A0000000500007A630101045B0000000600007A630101045C0000000700007A630101045D0000000800007A630101045E01efccdd
    $sendbuf['device'] = (hexdec('0x'.substr($str, 0, 8)));
    $sendbuf['order'] = (hexdec('0x'.substr($str, 8, 8)));
    $sendbuf['weight'] = (hexdec('0x'.substr($str, 16, 8)));
    $sendbuf['temp'] = (hexdec('0x'.substr($str, 24, 8)));
    $sendbuf['card_id'] = (hexdec('0x'.substr($str, 32, 8)));

    $crc = dechex($sendbuf['device'] ^ $sendbuf['order'] ^ $sendbuf['weight'] ^ $sendbuf['temp'] ^ $sendbuf['card_id'] );
    $crc = substr($crc, -4);
    return $crc = str_pad($crc,4,"0",STR_PAD_LEFT);
}

/**
 * Str2Hex
 */
function str2Hex($str, $length = 8){
    $hex = dechex($str);
    return $hex = str_pad($hex,$length,"0",STR_PAD_LEFT);
}

function bin2Bin($str){
        $v =  reverseStr($str);
    $data = implode('',$strArr);
    return $data;
}

function reverseStr($str) {
    $hex = bin2hex($str);

    $strlen = strlen($hex);
    $hex_array = array();
    $newhex = '';
    $j = -1;
    for($i=0; $i<$strlen; $i++){
        if($i%4 == 0){
            $j++;
            $hex_array[$j] = $hex[$i];
        }else{
            $hex_array[$j] .= $hex[$i];
        }
    }
    $hex_array = array_reverse($hex_array);
    foreach($hex_array as $v){
        $newhex .= $v;
    }

    $bin = "";
    $i = 0;
    do {
        $bin .= chr(hexdec($newhex{$i}.$newhex{($i + 1)}));
        $i += 2;
    } while ($i < strlen($newhex));
    return $bin;
}

function mbStrSplit ($string, $len=2) {
    $start = 0;
    $strlen = mb_strlen($string);
    while ($strlen) {
        $array[] = mb_substr($string,$start,$len,"utf8");
        $string = mb_substr($string, $len, $strlen,"utf8");
        $strlen = mb_strlen($string);
    }
    return $array;
}