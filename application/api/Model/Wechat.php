<?php


namespace app\api\model;


use think\cache\driver\Redis;
use think\Db;
use think\Model;

class Wechat extends Model
{

    //设置数据表名
    protected $table = 'mrs_wechats';

    /**
     * 获取小程序信息
     * @return array|mixed|null|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getWechatInfo()
    {
        //查询微信公众号信息
        $redis = new Redis();
        $wechatInfo = $redis->get('wechatInfo');
        if (empty($wechatInfo)) {
            $where = [];
            $where[] = ['is_actived' => 1];
            $wechatInfo = Db::table('mrs_wechats')->where($where)->find();
            if (empty($wechatInfo)) {
                mobile_notice('小程序未绑定');
            } else {
                $redis->set('wechatInfo', $wechatInfo);
            }
        }
        return $wechatInfo;
    }

}
