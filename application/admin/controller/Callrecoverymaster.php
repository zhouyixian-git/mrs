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
    public function index(Request $request){
        $master_name = $request->param('master_name');
        $is_actived = $request->param('is_actived');
        $master_phone_no = $request->param('master_phone_no');
        $where = [];
        if (!empty($master_name)) {
            $where[] = ['master_name', 'like', "%$master_name%"];
        }
        if (!empty($is_actived)) {
            $where[] = ['is_actived', '=', $is_actived];
        }
        if (!empty($master_phone_no)) {
            $where[] = ['master_phone_no', 'like', "%$master_phone_no%"];
        }

        $masterList = Db::table('mrs_call_recovery_master')
            ->order('create_time')
            ->where($where)
            ->paginate(8, false, [ 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $masterList->render();
        $count = Db::table('mrs_call_recovery_master')->count();

        $this->assign('master_name', $master_name);
        $this->assign('is_actived', $is_actived);
        $this->assign('master_phone_no', $master_phone_no);
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


            $masterM = new \app\admin\model\Callrecoverymaster();
            $masterM->save($data);

            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }


    public function update(Request $request){
        if ($request->isPost()) {
            $master_id = $request->post('master_id');
            $data = array();
            $data['master_name']= $request->post('master_name');
            $data['master_phone_no']= $request->post('master_phone_no');
            $data['lat'] = $request->post('lat');
            $data['lng'] = $request->post('lng');
            $data['address'] = $request->post('address');
            $data['is_actived'] = $request->post('is_actived');

            $validate = new \app\admin\validate\Callrecoverymaster();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }


            $masterM = new \app\admin\model\Callrecoverymaster();
            $masterM->save($data,['master_id' => $master_id]);

            echo $this->successJson();
            return;
        }

        $master_id = $request->get('master_id');
        if(empty($master_id)){
            $this->error('缺少关键参数');
        }
        $master= Db::table("mrs_call_recovery_master")
            ->where(array('master_id' => $master_id))
            ->find();

        $this->assign('master', $master);
        return $this->fetch();
    }


    /**
     * 删除上门人员
     * @param Request $request
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $master_id = $request->post('master_id');

            if (empty($master_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            \app\admin\model\Callrecoverymaster::where('master_id', $master_id)->delete();

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
