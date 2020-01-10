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
function recordLog($log_content= 'test',$log_file='log.txt')
{
    $filename = '../public/log/'.$log_file;
    $content = "[". date('Y-m-d H:i:s') ."]".$log_content;
    if(file_exists($filename)){
        $last_content = file_get_contents($filename);
        $content = $last_content."\r\n".$content ;
    }
    file_put_contents($filename, $content);
}

function sendSms($phone,$code, $code2){

    $accessKeyId = '';
    $accessSecret = '';
    $sign = '';

    $smsApi = Db::table('mrs_api')->where(array('api_code' => 'aliyunsms', 'status' => '1'))->find();

    if(empty($smsApi)){
        return errorJson('1', 'Not found the sms api!');
    }
    $params = Db::table('mrs_api_params')->where(array('api_id' => $smsApi['api_id']))->select();
    if(is_array($params) && count($params)){
        foreach ($params as $k=>$param){
            if($param['param_code'] == 'AccessKeyID'){
                $accessKeyId = $param['param_value'];
            }else if($param['param_code'] == 'AccessKeySecret'){
                $accessSecret = $param['param_value'];
            }else if($param['param_code'] == 'sign'){
                $sign = $param['param_value'];
            }
        }
    }

    if(empty($accessKeyId) || empty($accessSecret) || empty($sign)){
        return errorJson('1', '配置参数缺失!');
    }

    echo '$accessKeyId->'.$accessKeyId;
    echo '$accessSecret->'.$accessSecret;
    echo '$sign->'.$sign;
    exit;

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
    $request->setTemplateCode($code2);
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

    return $acsResponse;

}



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
    if(!empty($data)) {
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