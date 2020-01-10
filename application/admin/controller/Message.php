<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/9 0009
 * Time: 22:58
 */

namespace app\admin\controller;


use think\Db;
use think\Request;

class Message extends Base
{

    /**
     * 系统消息列表
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        $is_actived = $request->param('is_actived');
        $msg_title = $request->param('msg_title');
        $where = [];
        if (!empty($is_actived)) {
            $where[] = ['is_actived', '=', $is_actived];
        }
        if (!empty($msg_title)) {
            $where[] = ['msg_title', 'like', "%$msg_title%"];
        }

        $messageList = Db::table('mrs_system_msg')
            ->where($where)
            ->order('create_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $messageList->render();
        $count = Db::table('mrs_system_msg')->where($where)->count();

        $this->assign('is_actived', $is_actived);
        $this->assign('msg_title', $msg_title);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('messageList', $messageList);
        return $this->fetch();
    }

    /**
     * 添加系统消息
     * @param Request $request
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data = [];
            $data['msg_title'] = $request->post('msg_title');
            $data['msg_content'] = $request->post('msg_content');
            $data['is_actived'] = $request->post('is_actived');

            $validate = new \app\admin\validate\Message();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $data['create_user_id'] = parent::$loginAdmin['admin_id'];
            $data['create_time'] = time();

            $message = new \app\admin\model\Message();
            $message->save($data);
            echo $this->successJson();
            return;
        }
        return $this->fetch();
    }

    /**
     * 修改系统消息
     * @param Request $request
     */
    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $data = [];
            $message_id = $request->post('message_id');
            $data['msg_title'] = $request->post('msg_title');
            $data['msg_content'] = $request->post('msg_content');
            $data['is_actived'] = $request->post('is_actived');

            $validate = new \app\admin\validate\Message();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $message = new \app\admin\model\Message();
            $message->save($data, ['msg_id' => $message_id]);
            echo $this->successJson();
            return;
        }

        $message_id = $request->get('message_id');
        if (empty($message_id)) {
            $this->errorJson('关键数据错误');
        }
        $message = \app\admin\model\Message::where('msg_id', $message_id)->find();

        $this->assign('message', $message);
        return $this->fetch();
    }

    /**
     * 删除系统消息
     * @param Request $request
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $message_id = $request->post('message_id');

            if (empty($message_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            \app\admin\model\Message::where('msg_id', $message_id)->delete();
            echo $this->successJson();
            exit;
        }
    }
}
