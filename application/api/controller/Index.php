<?php
namespace app\api\controller;

use think\Db;
use think\Request;
use think\Crypt\Driver\Crypt;

class Index extends Base
{
    public function index()
    {
        $wechatModel = new \app\api\model\Wechat();
        $wechatInfo = $wechatModel->getWechatInfo();
        if (empty($wechatInfo)) {
            echo $this->errorJson(1, '小程序未绑定');
            exit;
        }
        $appid = $wechatInfo['app_id'];
        $appsecret = $wechatInfo['app_secret'];

        $accessToken = $wechatModel->getAccessToken($appid, $appsecret);
        if(empty($accessToken)){
            echo 'get accessToken fail' ;exit;
        }

        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$accessToken.'&openid=ouxag4n8-9_J4DdCq5hQmEOR0dSg';
        $res = doPostHttp($url, json_encode(array()));
        echo $res;

        exit;
    }


    function strToHex($str){
        $hex="";
        for($i=0;$i<strlen($str);$i++)
            $hex.=dechex(ord($str[$i]));
        $hex=strtoupper($hex);
        return $hex;
    }

    // 短信测试接口
    public function smsdemo()
    {
        $data['mobile'] = '18813974700';
        $data['tpl_code'] = 'sms_vcode';   // 模板ID
//        $result = sendSms($data['mobile'],$data['tpl_code']);
//        var_dump($result);
    }

    /**
     * @param Request $request
     * @searchkey 搜索地址
     */
    public function getpostcode(Request $request){
        $searchkey = $request->get('searchkey');

        $url = 'http://cpdc.chinapost.com.cn/web/index.php?m=postsearch&c=index&a=ajax_addr&searchkey='.$searchkey;

        $result = doPostHttp($url, '');
        echo $result;
        exit;
    }
}
