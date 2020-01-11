<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/11 0011
 * Time: 22:08
 */

namespace app\admin\controller;


use think\Db;
use think\Request;

class About extends Base
{

    /**
     * 关于我们
     * @param Request $request
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        if ($request->isPost()) {
            $data = [];
            $about_id = $request->post('about_id');
            $data['about_title'] = $request->post('about_title');
            $data['about_detail'] = $request->post('about_detail');

            $validate = new \app\admin\validate\About();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $about = new \app\admin\model\About();
            if(empty($about_id)){
                $data['create_time'] = time();
                $about->save($data);
            }else{
                $about->save($data, ['about_id' => $about_id]);
            }

            echo $this->successJson();
            return;
        }

        $about = Db::table('mrs_about')->find();
        $this->assign('about', $about);
        return $this->fetch();
    }

}
