<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/19 0019
 * Time: 12:12
 */

namespace app\admin\controller;


use app\admin\model\PortalCompany;
use think\Db;
use think\Exception;
use think\Request;

class Sysconfig extends Base
{

    /**
     * 站点配置
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        if ($request->isPost()) {
            $configParam = Db::table('mrs_system_setting')->field('setting_id, setting_code, input_type')->select();

            Db::startTrans();
            try {
                foreach ($configParam as $k => $v) {
                    if ($v['input_type'] == 2) {
                        Db::table('mrs_system_setting')->where('setting_id', $v['setting_id'])->update(['setting_value' => $request->post($v['setting_code'])]);
                    } else {
                        Db::table('mrs_system_setting')->where('setting_id', $v['setting_id'])->update(['setting_value' => $request->post($v['setting_code'])]);
                    }
                }
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
            }
            echo $this->successJson();
            exit;
        }

        $configData = Db::table('mrs_system_setting')->order('create_time asc')->select();

        foreach ($configData as $k => $v) {
            if ($v['input_type'] == '2') { //单选框，值用逗号分隔
                $set_value = explode(',', $v['setting_item']);
                $set_desc = explode(',', $v['setting_desc']);
                $configData[$k]['set_value'] = $set_value;
                $configData[$k]['set_desc'] = $set_desc;
            }
        }

        $this->assign('configData', $configData);
        return $this->fetch();
    }


    public function add(Request $request)
    {
        if ($request->isPost()) {

        }

        return $this->fetch();
    }

}
