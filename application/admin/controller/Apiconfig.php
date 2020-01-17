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

class Apiconfig extends Base
{
    public function index(){

        $apiList = Db::table('mrs_api')
            ->order('create_time')
            ->paginate(8, false, [ 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $apiList->render();
        $count = Db::table('mrs_api')->count();

        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('apiList', $apiList);

        return $this->fetch();
    }


    public function add(Request $request){
        if ($request->isPost()) {
            $data = array();
            $data['api_name']= $request->post('api_name');
            $data['api_code']= $request->post('api_code');
            $data['api_desc'] = $request->post('api_desc');
            $data['api_address'] = $request->post('api_address');
            $data['status'] = $request->post('status');
            $data['create_time'] = time();

            $validate = new \app\admin\validate\Api();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }


            $api = new \app\admin\model\Api();
            $api->save($data);

            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }


    public function update(Request $request){
        if ($request->isPost()) {
            $api_id= $request->post('api_id');
            $data = array();
            $data['api_name']= $request->post('api_name');
            $data['api_code']= $request->post('api_code');
            $data['api_desc'] = $request->post('api_desc');
            $data['api_address'] = $request->post('api_address');
            $data['status'] = $request->post('status');
            $data['create_time'] = time();

            $validate = new \app\admin\validate\Api();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }


            $api = new \app\admin\model\Api();
            $api->save($data,['api_id' => $api_id]);

            echo $this->successJson();
            return;
        }
        $api_id = $request->get('api_id');

        if(empty($api_id)){
            exit('缺少关键参数');
            return;
        }
        $api = Db::table("mrs_api")->where(array('api_id' => $api_id))->find();

        $this->assign('api', $api);
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
            $api_id = $request->post('api_id');

            if (empty($api_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            \app\admin\model\Api::where('api_id', $api_id)->delete();
            \app\admin\model\ApiParams::where('api_id', $api_id)->delete();

            echo $this->successJson();
            exit;
        }
    }

    public function param(Request $request){
        $api_id = $request->get('api_id');

        $api = Db::table("mrs_api")
            ->where(array('api_id' => $api_id))
            ->find();
        if(empty($api)){
            exit('关键参数错误');
        }

        $where = [];
        $where[] = ['api_id','=', $api_id];

        $apiParamsList = Db::table('mrs_api_params')
            ->where($where)
            ->paginate(8, false, [ 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $apiParamsList->render();
        $count = Db::table('mrs_api_params')->count();

        $this->assign('api_id', $api_id);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('apiParamList', $apiParamsList);

        return $this->fetch();
    }


    public function addparam(Request $request){
        if ($request->isPost()) {
            $data = array();
            $data['api_id']= $request->post('api_id');
            $data['param_name'] = $request->post('param_name');
            $data['param_desc'] = $request->post('param_desc');
            $data['param_code'] = $request->post('param_code');
            $data['param_value'] = $request->post('param_value');

            $validate = new \app\admin\validate\Apiparams();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }


            $apiParams = new \app\admin\model\ApiParams();
            $apiParams->save($data);

            echo $this->successJson();
            return;
        }
        $api_id = $request->get("api_id");

        if(empty($api_id)){
            exit('关键参数丢失');
        }

        $this->assign('api_id', $api_id);
        return $this->fetch();
    }


    public function updateparam(Request $request){
        if ($request->isPost()) {
            $param_id = $request->post('param_id');

            $data = array();
            $data['api_id']= $request->post('api_id');
            $data['param_name'] = $request->post('param_name');
            $data['param_desc'] = $request->post('param_desc');
            $data['param_code'] = $request->post('param_code');
            $data['param_value'] = $request->post('param_value');

            $validate = new \app\admin\validate\Apiparams();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }


            $apiParams = new \app\admin\model\ApiParams();
            $apiParams->save($data, ['param_id' => $param_id]);

            echo $this->successJson();
            return;
        }
        $param_id = $request->get("param_id");
        if(empty($param_id)){
            exit('关键参数丢失');
        }
        $param = Db::table('mrs_api_params')
            ->where(array('param_id' => $param_id))
            ->find();

        $this->assign('param', $param);
        return $this->fetch();
    }


    /**
     * 删除接口参数
     * @param Request $request
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function deleteparam(Request $request)
    {
        if ($request->isPost()) {
            $param_id = $request->post('param_id');

            if (empty($param_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            \app\admin\model\ApiParams::where('param_id', $param_id)->delete();

            echo $this->successJson();
            exit;
        }
    }
}
