<?php
namespace app\api\controller;

use think\Db;
use think\Request;

class Index extends Base
{
    public function index()
    {
        connectModbus();
        exit;
//        $address = Db::table('mrs_user_address')->where(array('user_id' => 1))->order('is_default desc')->find();
//        var_dump($address);
//        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">12载初心不改（2006-2018） - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
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
