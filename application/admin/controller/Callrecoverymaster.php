<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/19 0019
 * Time: 12:12
 */

namespace app\admin\controller;


use think\Db;
use think\Exception;
use think\Request;

class Callrecoverymaster extends Base
{
    public function index(){

        $masterList = Db::table('mrs_call_recovery_master')
            ->order('create_time')
            ->paginate(8, false, [ 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $masterList->render();
        $count = Db::table('mrs_call_recovery_master')->count();

        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('masterList', $masterList);

        return $this->fetch();
    }


    public function add(Request $request){
        if ($request->isPost()) {
            $data = array();
            $data['master_name']= $request->post('master_name');
            $data['master_phone_no']= $request->post('master_phone_no');
            $data['lat'] = $request->post('lat');
            $data['lng'] = $request->post('lng');
            $data['address'] = $request->post('address');
            $data['is_actived'] = $request->post('is_actived');
            $data['create_time'] = time();

            $validate = new \app\admin\validate\Callrecoverymaster();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }


            $api = new \app\admin\model\Callrecoverymaster();
            $api->save($data);

            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }


    public function update(Request $request){
        if ($request->isPost()) {
            $tpl_id= $request->post('tpl_id');
            $data = array();
            $data['tpl_name']= $request->post('tpl_name');
            $data['tpl_code']= $request->post('tpl_code');
            $data['aliyun_code'] = $request->post('aliyun_code');
            $data['tpl_content'] = $request->post('tpl_content');

            $validate = new \app\admin\validate\Smstpl();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }


            $api = new \app\admin\model\Smstpl();
            $api->save($data,['tpl_id' => $tpl_id]);

            echo $this->successJson();
            return;
        }
        $tpl_id = $request->get('tpl_id');

        if(empty($tpl_id)){
            exit('缺少关键参数');
            return;
        }
        $smsTpl= Db::table("mrs_sms_tpl")
            ->where(array('tpl_id' => $tpl_id))
            ->find();

        $this->assign('smsTpl', $smsTpl);
        return $this->fetch();
    }


    /**
     * 删除接口
     * @param Request $request
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $tpl_id = $request->post('tpl_id');

            if (empty($tpl_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            \app\admin\model\Smstpl::where('tpl_id', $tpl_id)->delete();

            echo $this->successJson();
            exit;
        }
    }


    /**
     * 跳转地图
     * @param Request $request
     * @return mixed
     */
    public function map(Request $request)
    {

        //查询百度地图AK
        $apiM = new \app\admin\model\Api();
        $ak = $apiM->getParam('baiduMap', 'ak');

        $lng = $request->get('lng');
        $lat = $request->get('lat');
        $keyword = $request->get('keyword');

        $this->assign('ak', $ak);
        $this->assign('lng', $lng);
        $this->assign('lat', $lat);
        $this->assign('keyword', $keyword);
        return $this->fetch();
    }


}
