<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/9 0009
 * Time: 21:10
 */

namespace app\admin\model;


use think\Model;

class Callrecoverycate extends Model
{
    //设置数据表名
    protected $table = 'mrs_call_recovery_cate';

    /**
     * 获取分类树
     * @param int $parent_id
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCateTree($parent_id = 0)
    {
        $cates = $this->field('cate_name as text,cate_id,parent_id')->where('parent_id', $parent_id)->order('order_no asc')->select()->toArray();
        foreach ($cates as $k => $v) {
            $childCates = $this->getCateTree($v['cate_id']);
            if ($childCates) {
                $cates[$k]['nodes'] = $childCates;
            }
        }
        return $cates;
    }

    /**
     * 获取分类列表
     * @param int $parent_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getParentCate($parent_id = 0)
    {

        $firstCateList = \app\admin\model\Callrecoverycate::where('cate_level', '=', 1)->order('order_no', 'asc')->select();
        $secondCateList = \app\admin\model\Callrecoverycate::where('cate_level', '=', 2)->order('order_no', 'asc')->select();
        $cateData = [];
        foreach ($firstCateList as $k1 => $v1) {
            $cateData[] = ['cate_id' => $v1['cate_id'], 'cate_name' => $v1['cate_name']];
            foreach ($secondCateList as $k2 => $v2) {
                if ($v1['cate_id'] == $v2['parent_id']) {
                    $cateData[] = ['cate_id' => $v2['cate_id'], 'cate_name' => '　' . $v2['cate_name']];
                }
            }
        }
        return $cateData;
    }

    /**
     * 获取分类列表
     * @param int $parent_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCateList($parent_id = 0)
    {
        $where = [['parent_id', '=', $parent_id]];
        $cates = $this->field('cate_name,cate_id,parent_id')->where($where)->select()->toArray();
        foreach ($cates as $k => $v) {
            $childCate = $this->getCateList($v['cate_id']);
            if ($childCate) {
                $cates[$k]['hasChild'] = '1';
                $cates[$k]['childCate'] = $childCate;
            } else {
                $cates[$k]['hasChild'] = '0';
                $cates[$k]['childCate'] = [];
            }
        }
        return $cates;
    }

}
