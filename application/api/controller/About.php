<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/12 0012
 * Time: 9:57
 */

namespace app\api\controller;


use think\Db;
use think\Request;

class About extends Base
{

    /**
     * 获取关于我们信息
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAboutInfo(Request $request)
    {
        if ($request->isPost()) {
            $about = Db::table('mrs_about')->find();

            if($about){
                $about['create_time'] = date('Y-m-d H:i', $about['create_time']);

                echo $this->successJson($about);
                exit;
            }else{
                echo $this->errorJson(1, '没有相关信息');
                exit;
            }
        }
    }

}