<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/14 0014
 * Time: 20:50
 */

namespace app\controller;


use app\model\Admin;
use app\model\RoleMenu;
use think\Db;
use think\Request;

class Role extends Base
{

    /**
     * 角色列表
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
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

    /**
     * 添加角色
     * @param Request $request
     * @return mixed|void
     */
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

    /**
     * 修改角色
     * @param Request $request
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
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
        if(empty($role_id)){
            $this->error('关键数据错误');
        }
        $role = \app\model\Role::where('role_id', $role_id)->find();

        $this->assign('role', $role);
        return $this->fetch();
    }

    /**
     * 删除角色
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
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

            Db::startTrans();
            try {
                Db::table('eas_role')->where(['role_id' => $role_id])->delete();
                Db::table('eas_role_menu')->where(['role_id' => $role_id])->delete();
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }
            echo $this->successJson();
            exit;
        }
    }

    /**
     * 角色授权
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function auth(Request $request)
    {
        if ($request->isPost()) {
            $role_id = $request->post('role_id');

            if (empty($role_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            $parentMenu = $request->post('parent_menu/a');
            $childMenu = $request->post('child_menu/a');
            $menuList = [];
            if ($parentMenu) {
                foreach ($parentMenu as $k => $v) {
                    $menuList[] = ['role_id' => $role_id, 'menu_id' => $v];
                }
                if ($childMenu) {
                    foreach ($childMenu as $k1 => $v1) {
                        $menuList[] = ['role_id' => $role_id, 'menu_id' => $v1];
                    }
                }
            }

            Db::startTrans();
            try {
                Db::table('eas_role_menu')->where(['role_id' => $role_id])->delete();
                Db::table('eas_role_menu')->insertAll($menuList);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }

            echo $this->successJson();
            exit;
        }

        $role_id = $request->get('role_id');
        if(empty($role_id)){
            $this->error('关键数据错误');
        }
        $parentMenuList = \app\model\Menu::where('parent_id', '0')->order('order_no', 'asc')->select();
        $childMenuList = \app\model\Menu::where('parent_id', '>', '0')->order('order_no', 'asc')->select();
        $menuList = RoleMenu::where('role_id', $role_id)->field('menu_id')->select()->toArray();
        $menuList = array_column($menuList,'menu_id');

        $this->assign('role_id', $role_id);
        $this->assign('childMenuList', $childMenuList);
        $this->assign('parentMenuList', $parentMenuList);
        $this->assign('menuList', $menuList);
        return $this->fetch();
    }

}
