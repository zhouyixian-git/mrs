<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/8 0008
 * Time: 20:49
 */

namespace app\admin\controller;


use app\admin\model\RoleCate;
use think\Request;

class Callrecoverycate extends Base
{

    /**
     * 菜单列表
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $cate = new \app\admin\model\Callrecoverycate();
        $cateData = $cate->getCateTree(0);

        $parentCateList = $cate->getParentCate(0);

        $this->assign('cateList', json_encode($cateData));  //这里将菜单数据转成json字符串，因为前端js无法赋值
        $this->assign('parentCateList', $parentCateList);
        return $this->fetch();
    }

    /**
     * 添加菜单
     * @param Request $request
     * @return mixed|void
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $cate_name = $request->post('cate_name');
            $cate_level = $request->post('cate_level');
            $parent_id = $request->post('parent_id');
            $order_no = $request->post('order_no');

            if ($cate_level == 1) {
                $parent_id = 0;
            }

            $data = [
                'cate_name' => $cate_name,
                'cate_level' => $cate_level,
                'parent_id' => $parent_id,
                'order_no' => $order_no
            ];
            $validate = new \app\admin\validate\Callrecoverycate();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $cate = new \app\admin\model\Callrecoverycate();
            $cate->save($data);
            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }

    /**
     * 编辑菜单
     * @param Request $request
     */
    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $cate_name = $request->post('cate_name');
            $order_no = $request->post('order_no');
            $cate_id = $request->post('cate_id');
            $cate_level = $request->post('cate_level');
            $parent_id = $request->post('parent_id');

            if (empty($cate_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            $data = [
                'cate_name' => $cate_name,
                'order_no' => $order_no,
                'cate_level' => $cate_level,
                'parent_id' => $parent_id
            ];
            $validate = new \app\admin\validate\Callrecoverycate();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $cate = new \app\admin\model\Callrecoverycate();
            $cate->save($data, ['cate_id' => $cate_id]);
            echo $this->successJson();
            return;
        }
    }

    /**
     * 根据菜单id查询菜单
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCateById(Request $request)
    {
        if ($request->isPost()) {
            $cate_id = $request->post('cate_id');
            if (empty($cate_id)) {
                echo $this->errorJson(0, '关键参数错误！');
                exit;
            }

            $cateInfo = \app\admin\model\Callrecoverycate::where('cate_id', $cate_id)->find();
            echo $this->successJson($cateInfo);
            return;
        }
    }

    /**
     * 删除菜单
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $cate_id = $request->post('cate_id');
            if (empty($cate_id)) {
                echo $this->errorJson(0, '关键参数错误！');
                exit;
            }

            \app\admin\model\Callrecoverycate::where('cate_id', '=', $cate_id)->whereOr('parent_id', '=', $cate_id)->delete();
            echo $this->successJson();
            return;
        }
    }

}
