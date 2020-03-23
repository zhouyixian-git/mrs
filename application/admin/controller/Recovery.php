<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/19 0019
 * Time: 9:47
 */

namespace app\admin\controller;


use think\Db;
use think\Request;
use think\Session;

class Recovery extends Base
{

    /**
     * 回收清单列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $recovery_record_sn = $request->param('recovery_record_sn');
        $phone_no = $request->param('phone_no');
        $site_name = $request->param('site_name');

        $where = [];

        if(!empty(self::$loginAdmin['roleRegion'])){
            $where[] = ['region_id', 'in', self::$loginAdmin['roleRegion']];
        }
        if (!empty($recovery_record_sn)) {
            $where[] = ['recovery_record_sn', 'like', "%$recovery_record_sn%"];
        }
        if (!empty($site_name)) {
            $where[] = ['site_name', 'like', "%$site_name%"];
        }
        if (!empty($phone_no)) {
            $where[] = ['phone_no', '=', $phone_no];
        }

        $recoveryList = Db::table('mrs_recovery_record')
            ->where($where)
            ->order('recovery_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page'])
            ;

        $this->assign('recovery_record_sn', $recovery_record_sn);
        $this->assign('phone_no', $phone_no);
        $this->assign('site_name', $site_name);
        $this->assign('recoveryList', $recoveryList);
        return $this->fetch();
    }

    public function detail(Request $request){
        $record_id = $request->get('record_id');

        if(empty($record_id)){
            exit('参数异常，请关闭后重试');
        }

        $detailList = Db::table('mrs_recovery_record_detail')->where('recovery_record_id','=',$record_id)->select();

        $this->assign('detailList', $detailList);
        return $this->fetch();
    }
}
