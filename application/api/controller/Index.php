<?php
namespace app\api\controller;

use think\Db;
use think\Request;
use think\Crypt\Driver\Crypt;

class Index extends Base
{
    public function index()
    {

        echo imgToBase64(APP_ROOT_PATH . '/uploads/images/other/20200121/2020012115212775856.png');
        exit;
        $userM = new \app\api\model\User();
        $res = $userM->searchFace('/uploads/images/other/20200121/2020012115212775856.png');
        var_dump($res);
        exit;
        $res = getFaceApiAccessToken();
        echo $res;
        exit;
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
