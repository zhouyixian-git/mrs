<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/16 0016
 * Time: 15:48
 */

namespace app\controller;


use think\Db;
use think\Request;

class Admin extends Base
{
    /**
     * 成员列表
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $admin_code = $request->param('admin_code');
        $admin_name = $request->param('admin_name');
        $admin_status = $request->param('admin_status');

        $where = [];
        if (!empty($admin_code)) {
            $where[] = ['t1.admin_code', '=', $admin_code];
        }
        if (!empty($admin_name)) {
            $where[] = ['t1.admin_name', '=', $admin_name];
        }
        if (!empty($admin_status)) {
            $where[] = ['t1.admin_status', '=', $admin_status];
        }

        $where[] = ['t1.admin_code', '<>', 'admin'];
        $adminList = Db::table('eas_admin')
            ->alias('t1')
            ->leftJoin('eas_role t2', 't1.role_id = t2.role_id')
            ->field('t1.admin_id,t1.admin_code,t1.admin_name,t1.admin_status,t1.admin_head,t1.admin_head,t1.admin_email,t1.last_login_time,t1.create_time,t1.update_time,t2.role_name')
            ->where($where)
            ->order('t1.admin_id desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $adminList->render();
        $count = Db::table('eas_admin')->alias('t1')->where($where)->count();

        $this->assign('admin_code', $admin_code);
        $this->assign('admin_name', $admin_name);
        $this->assign('admin_status', $admin_status);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('adminList', $adminList);
        return $this->fetch();
    }

    /**
     * 添加成员
     * @param Request $request
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $role_id = $request->post('role_id');
            $admin_code = $request->post('admin_code');
            $admin_name = $request->post('admin_name');
            $admin_pwd = $request->post('admin_pwd');
            $confirm_admin_pwd = $request->post('confirm_admin_pwd');
            $admin_status = $request->post('admin_status');
            $admin_mobile = $request->post('admin_mobile');
            $admin_email = $request->post('admin_email');

            $data = [
                'role_id' => $role_id,
                'admin_code' => $admin_code,
                'admin_name' => $admin_name,
                'admin_pwd' => $admin_pwd,
                'confirm_admin_pwd' => $confirm_admin_pwd,
                'admin_status' => $admin_status,
                'admin_mobile' => $admin_mobile,
                'admin_email' => $admin_email,
                'create_time' => time(),
                'update_time' => time()
            ];
            $validate = new \app\validate\Admin();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $adminCode = \app\model\Admin::where('admin_code', $admin_code)->find();
            if ($adminCode) {
                echo $this->errorJson(0, "成员账号[$admin_code]已经存在");
                return;
            }

            $data['admin_pwd'] = md5($admin_code . $admin_pwd . $admin_code);

            $admin = new \app\model\Admin();
            $admin->save($data);
            echo $this->successJson();
            return;
        }

        $roleList = \app\model\Role::order('role_id desc')->select();

        $this->assign('roleList', $roleList);
        return $this->fetch();
    }

    /**
     * 修改成员
     * @param Request $request
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $admin_id = $request->post('admin_id');
            $role_id = $request->post('role_id');
            $admin_code = $request->post('admin_code');
            $admin_name = $request->post('admin_name');
            $admin_status = $request->post('admin_status');
            $admin_mobile = $request->post('admin_mobile');
            $admin_email = $request->post('admin_email');

            $data = [
                'role_id' => $role_id,
                'admin_code' => $admin_code,
                'admin_name' => $admin_name,
                'admin_status' => $admin_status,
                'admin_mobile' => $admin_mobile,
                'admin_email' => $admin_email,
                'update_time' => time()
            ];
            $validate = new \app\validate\Admin();
            if (!$validate->scene('edit')->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $adminCode = \app\model\Admin::where([['admin_code', '=', $admin_code], ['admin_id', '<>', $admin_id]])->find();
            if ($adminCode) {
                echo $this->errorJson(0, "成员账号[$admin_code]已经存在");
                return;
            }

            $admin = new \app\model\Admin();
            $admin->save($data, ['admin_id' => $admin_id]);
            echo $this->successJson();
            return;
        }

        $admin_id = $request->get('admin_id');
        $admin = \app\model\Admin::where('admin_id', $admin_id)->find();

        $roleList = \app\model\Role::order('role_id desc')->select();

        $this->assign('admin', $admin);
        $this->assign('roleList', $roleList);
        return $this->fetch();
    }

    /**
     * 删除成员
     * @param Request $request
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $admin_id = $request->post('admin_id');

            if (empty($admin_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            $affected = \app\model\Admin::where('admin_id', $admin_id)->delete();
            if ($affected > 0) {
                echo $this->successJson();
                exit;
            } else {
                echo $this->errorJson(0, '删除失败');
                exit;
            }
        }
    }

    /**
     * 修改密码
     * @param Request $request
     * @return mixed
     */
    public function updatepwd(Request $request)
    {
        if ($request->isPost()) {
            $admin_id = $request->post('admin_id');
            $admin_code = $request->post('admin_code');
            $admin_pwd = $request->post('admin_pwd');
            $confirm_admin_pwd = $request->post('confirm_admin_pwd');

            $data = [
                'admin_id' => $admin_id,
                'admin_pwd' => $admin_pwd,
                'confirm_admin_pwd' => $confirm_admin_pwd,
                'update_time' => time()
            ];
            $validate = new \app\validate\Admin();
            if (!$validate->scene('updatepwd')->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $data['admin_pwd'] = md5($admin_code . $admin_pwd . $admin_code);

            $admin = new \app\model\Admin();
            $admin->save($data, ['admin_id' => $admin_id]);
            echo $this->successJson();
            return;
        }

        $admin_id = $request->get('admin_id');
        $admin_code = $request->get('admin_code');

        $this->assign('admin_id', $admin_id);
        $this->assign('admin_code', $admin_code);
        return $this->fetch();
    }

    /**
     * 修改资料
     * @param Request $request
     * @return mixed|void
     */
    public function updateinfo(Request $request)
    {
        if ($request->isPost()) {
            $admin_id = $request->post('admin_id');
            $admin_name = $request->post('admin_name');
            $admin_head = $request->post('admin_head');
            $admin_mobile = $request->post('admin_mobile');
            $admin_email = $request->post('admin_email');

            $data = [
                'admin_name' => $admin_name,
                'admin_head' => $admin_head,
                'admin_mobile' => $admin_mobile,
                'admin_email' => $admin_email
            ];

            $admin = new \app\model\Admin();
            $admin->save($data, ['admin_id' => $admin_id]);
            echo $this->successJson();
            return;
        }

        return $this->fetch();
    }

    /**
     * 更新用户密码
     * @param Request $request
     * @return mixed|void
     */
    public function updateuserpwd(Request $request)
    {
        if ($request->isPost()) {
            $admin_id = $request->post('admin_id');
            $admin_code = $request->post('admin_code');
            $old_pwd = $request->post('old_pwd');
            $admin_pwd = $request->post('admin_pwd');
            $confirm_admin_pwd = $request->post('confirm_admin_pwd');

            if (empty($old_pwd)) {
                echo $this->errorJson(0, '原密码不能为空！');
                return;
            }

            $data = [
                'admin_id' => $admin_id,
                'admin_pwd' => $admin_pwd,
                'confirm_admin_pwd' => $confirm_admin_pwd,
                'old_pwd' => $old_pwd,
                'update_time' => time()
            ];
            $validate = new \app\validate\Admin();
            if (!$validate->scene('updatepwd')->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $adminUser = \app\model\Admin::where('admin_id', $admin_id)->find();
            if (!$adminUser) {
                echo $this->errorJson(0, '用户数据丢失！');
                return;
            }

            if (md5($admin_code . $old_pwd . $admin_code) != $adminUser['admin_pwd']) {
                echo $this->errorJson(0, '原密码错误，请重新输入！');
                return;
            }
            $data['admin_pwd'] = md5($admin_code . $admin_pwd . $admin_code);

            if (md5($admin_code . $old_pwd . $admin_code) == $data['admin_pwd']) {
                echo $this->errorJson(0, '新密码与原密码不能相同！');
                return;
            }

            $admin = new \app\model\Admin();
            $admin->save($data, ['admin_id' => $admin_id]);
            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }

}
