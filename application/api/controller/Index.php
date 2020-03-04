<?php
namespace app\api\controller;

use think\Db;
use think\Request;
use think\Crypt\Driver\Crypt;

class Index extends Base
{
    public function index()
    {
        $sendbuf = array();
//        $str = '00 00 00 00 00 00 01 fa 00 00 00 00 00 00 00 00 00 00 00 00 01 fa cc dd';


        $str = '01 31 54 11 00 00 01 f5 00 00 00 00 00 00 00 00 00 00 00 00';
        echo $crc = calcCRC($str);
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
