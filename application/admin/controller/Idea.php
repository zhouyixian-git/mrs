<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/11 0011
 * Time: 21:34
 */

namespace app\admin\controller;


use think\Db;
use think\Request;

class Idea extends Base
{

    /**
     * 用户反馈列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $idea_title = $request->param('idea_title');
        $idea_type = $request->param('idea_type');
        $where = [];
        if (!empty($idea_title)) {
            $where[] = ['t1.idea_title', 'like', "%$idea_title%"];
        }
        if (!empty($idea_type)) {
            $where[] = ['t1.idea_type', '=', $idea_type];
        }

        $where[] = ['t1.is_del', '=', 2];
        $ideaList = Db::table('mrs_idea')
            ->alias('t1')
            ->field('t1.idea_id,t1.idea_title,t1.idea_type,t1.idea_content,t1.user_id,t1.idea_image,t1.create_time,t2.nick_name')
            ->leftJoin('mrs_user t2', 't1.user_id = t2.user_id')
            ->where($where)
            ->order('t1.create_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $ideaList->render();
        $count = Db::table('mrs_idea')->alias('t1')->where($where)->count();

        $this->assign('idea_title', $idea_title);
        $this->assign('idea_type', $idea_type);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('ideaList', $ideaList);
        return $this->fetch();
    }

    /**
     * 查看详情
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view(Request $request)
    {
        $idea_id = $request->get('idea_id');
        if (empty($idea_id)) {
            $this->error('关键数据错误');
        }

        $idea = Db::table('mrs_idea')
            ->alias('t1')
            ->field('t1.idea_id,t1.idea_title,t1.idea_type,t1.idea_content,t1.user_id,t1.idea_image,t1.create_time,t2.user_name')
            ->leftJoin('mrs_user t2', 't1.user_id = t2.user_id')
            ->where('idea_id', '=', $idea_id)
            ->find();

        if (!empty($idea['idea_image'])) {
            $idea['idea_image_list'] = explode(',', $idea['idea_image']);
        }

        $this->assign('idea', $idea);
        return $this->fetch();
    }

    /**
     * 删除用户反馈信息
     * @param Request $request
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $idea_id = $request->post('idea_id');

            if (empty($idea_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            \app\admin\model\Idea::where('idea_id', $idea_id)->update(['is_del' => 1]);
            echo $this->successJson();
            exit;
        }
    }

}
