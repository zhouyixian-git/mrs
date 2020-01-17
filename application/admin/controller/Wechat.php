<?php


namespace app\admin\controller;

use think\Request;

class Wechat extends Base
{

    /**
     * 微信配置信息
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if ($request->isPost()) {
            $wechat_id = $request->post('wechat_id');
            $data['wechat_name'] = $request->post('wechat_name');
            $data['app_id'] = $request->post('app_id');
            $data['app_secret'] = $request->post('app_secret');
            $data['mch_id'] = $request->post('mch_id');
            $data['mch_key'] = $request->post('mch_key');
            $data['is_actived'] = $request->post('is_actived');

            $validate = new \app\admin\validate\Wechat();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            if (empty($wechat_id)) { //新增
                $wechat = new \app\admin\model\Wechat();
                $data['create_time'] = time();
                $wechat->save($data);
                echo $this->successJson();
                return;
            } else { //修改
                $wechat = new \app\admin\model\Wechat();
                $wechat->save($data, ['wechat_id' => $wechat_id]);
                echo $this->successJson();
                return;
            }
        }

        $wechat = \app\admin\model\Wechat::find();

        $this->assign('wechat', $wechat);
        return $this->fetch();
    }

}
