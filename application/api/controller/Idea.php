<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/12 0012
 * Time: 10:03
 */

namespace app\api\controller;


use think\Db;
use think\Request;

class Idea extends Base
{

    /**
     * 用户意见反馈
     * @param Request $request
     */
    public function createIdea(Request $request){
        if($request->isPost()){
            $data['idea_title'] = '用户意见反馈';
            $data['idea_type'] = $request->post('idea_type');
            $data['idea_content'] = $request->post('idea_content');
            $data['user_id'] = $request->post('user_id');
            $data['idea_image'] = $request->post('idea_image');
            $data['create_time'] = time();

            if(empty($data['user_id'])){
                echo $this->errorJson(1, '缺少关键数据user_id');
                exit;
            }
            if(empty($data['idea_type'])){
                echo $this->errorJson(1, '缺少关键数据idea_type');
                exit;
            }
            if(empty($data['idea_content'])){
                echo $this->errorJson(1, '缺少关键数据idea_content');
                exit;
            }

            $user = Db::table('mrs_user')->where('user_id', '=', $data['user_id'])->find();
            if(!$user){
                echo $this->errorJson(1, '用户信息不存在');
                exit;
            }

            $idea = new \app\api\model\Idea();
            $idea->save($data);
            echo $this->successJson();
            exit;
        }
    }

}