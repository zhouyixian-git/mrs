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

class Recoverycate extends Base
{

    /**
     * 分类列表
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $cate = new \app\admin\model\Recoverycate();
        $cateData = $cate->getCateTree(0);

        $parentCateList = $cate->getParentCate(0);

        $this->assign('cateList', json_encode($cateData));  //这里将菜单数据转成json字符串，因为前端js无法赋值
        $this->assign('parentCateList', $parentCateList);
        return $this->fetch();
    }

    /**
     * 添加分类
     * @param Request $request
     * @return mixed|void
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $cate_name = $request->post('cate_name');
            $cate_level = $request->post('cate_level');
            $cate_code = $request->post('cate_code');
            $integral = $request->post('integral');
            $unit_weight = $request->post('unit_weight');
            $bg_icon_img = $request->post('bg_icon_img');
            $parent_id = $request->post('parent_id');
            $order_no = $request->post('order_no');

            if ($cate_level == 1) {
                $parent_id = 0;
            }

            $data = [
                'cate_name' => $cate_name,
                'cate_level' => $cate_level,
                'cate_code' => $cate_code,
                'integral' => $integral,
                'unit_weight' => $unit_weight,
                'bg_icon_img' => $bg_icon_img,
                'parent_id' => $parent_id,
                'order_no' => $order_no
            ];
            $validate = new \app\admin\validate\Recoverycate();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $cate = new \app\admin\model\Recoverycate();
            $cate->save($data);
            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }

    /**
     * 编辑分类
     * @param Request $request
     */
    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $cate_name = $request->post('cate_name');
            $cate_code = $request->post('cate_code');
            $integral = $request->post('integral');
            $unit_weight = $request->post('unit_weight');
            $bg_icon_img = $request->post('bg_icon_img');
            $order_no = $request->post('order_no');
            $cate_id = $request->post('cate_id');

            if (empty($cate_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            $data = [
                'cate_name' => $cate_name,
                'cate_code' => $cate_code,
                'integral' => $integral,
                'unit_weight' => $unit_weight,
                'bg_icon_img' => $bg_icon_img,
                'order_no' => $order_no
            ];
            $validate = new \app\admin\validate\Recoverycate();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $cate = new \app\admin\model\Recoverycate();
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

            $cateInfo = \app\admin\model\Recoverycate::where('cate_id', $cate_id)->find();
            echo $this->successJson($cateInfo);
            return;
        }
    }

    /**
     * 删除分类
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

            \app\admin\model\Recoverycate::where('cate_id', '=', $cate_id)->whereOr('parent_id', '=', $cate_id)->delete();
            echo $this->successJson();
            return;
        }
    }

}
