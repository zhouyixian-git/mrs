<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/14 0014
 * Time: 20:50
 */

namespace app\controller;


use app\model\Admin;
use think\Db;
use think\Request;

class Role extends Base
{

    public function index(Request $request)
    {

        $role_code = $request->param('role_code');
        $role_name = $request->param('role_name');
        $where = [];
        if (!empty($role_code)) {
            $where[] = ['role_code', '=', $role_code];
        }
        if (!empty($role_name)) {
            $where[] = ['role_name', '=', $role_name];
        }

        $roleList = Db::table('eas_role')
            ->where($where)
            ->order('role_id desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $roleList->render();
        $count = Db::table('eas_role')->where($where)->count();

        $this->assign('role_code', $role_code);
        $this->assign('role_name', $role_name);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('roleList', $roleList);
        return $this->fetch();
    }

    public function add(Request $request)
    {
        if ($request->isPost()) {
            $role_code = $request->post('role_code');
            $role_name = $request->post('role_name');
            $role_remark = $request->post('role_remark');

            $data = [
                'role_code' => $role_code,
                'role_name' => $role_name,
                'role_remark' => $role_remark
            ];
            $validate = new \app\validate\Role();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $role = new \app\model\Role();
            $role->save($data);
            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }

    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $role_id = $request->post('role_id');
            $role_code = $request->post('role_code');
            $role_name = $request->post('role_name');
            $role_remark = $request->post('role_remark');

            if (empty($role_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            $data = [
                'role_code' => $role_code,
                'role_name' => $role_name,
                'role_remark' => $role_remark
            ];
            $validate = new \app\validate\Role();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $role = new \app\model\Role();
            $role->save($data, ['role_id' => $role_id]);
            echo $this->successJson();
            return;
        }

        $role_id = $request->get('role_id');
        $role = \app\model\Role::where('role_id', $role_id)->find();

        $this->assign('role', $role);
        return $this->fetch();
    }

    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $role_id = $request->post('role_id');

            if (empty($role_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            $count = Admin::where('role_id', $role_id)->count();
            if ($count > 0) {
                echo $this->errorJson(0, '该角色下存在管理员信息，不可删除！');
                exit;
            }

            $affected = \app\model\Role::where('role_id', $role_id)->delete();
            if ($affected > 0) {
                echo $this->successJson();
                exit;
            } else {
                echo $this->errorJson(0, '删除失败');
                exit;
            }
        }
    }

}
