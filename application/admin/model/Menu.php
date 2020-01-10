<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/9 0009
 * Time: 21:10
 */

namespace app\admin\model;


use think\Model;

class Menu extends Model
{
    //设置数据表名
    protected $table = 'eas_menu';

    /**
     * 获取菜单树
     * @param int $parent_id
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenuTree($parent_id = 0)
    {
        $menus = $this->field('menu_name as text,menu_id,parent_id')->where('parent_id', $parent_id)->order('order_no asc')->select()->toArray();
        foreach ($menus as $k => $v) {
            $childMenus = $this->getMenuTree($v['menu_id']);
            if ($childMenus) {
                $menus[$k]['nodes'] = $childMenus;
            }
        }
        return $menus;
    }

    /**
     * 获取菜单列表
     * @param int $parent_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getParentMenu($parent_id = 0)
    {

        $firstMenuList = \app\admin\model\Menu::where('menu_level', '=', 1)->order('order_no', 'asc')->select();
        $secondMenuList = \app\admin\model\Menu::where('menu_level', '=', 2)->order('order_no', 'asc')->select();
        $menuData = [];
        foreach ($firstMenuList as $k1 => $v1) {
            $menuData[] = ['menu_id' => $v1['menu_id'], 'menu_name' => $v1['menu_name']];
            foreach ($secondMenuList as $k2 => $v2) {
                if ($v1['menu_id'] == $v2['parent_id']) {
                    $menuData[] = ['menu_id' => $v2['menu_id'], 'menu_name' => '　' . $v2['menu_name']];
                }
            }
        }
        return $menuData;
    }

    /**
     * 获取菜单列表
     * @param int $parent_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMenuList($parent_id = 0)
    {
        $where = [['parent_id', '=', $parent_id], ['menu_code', '<>', 'menu_mgr']];
        $menus = $this->field('menu_name,menu_id,parent_id')->where($where)->select()->toArray();
        foreach ($menus as $k => $v) {
            $childMenu = $this->getMenuList($v['menu_id']);
            if ($childMenu) {
                $menus[$k]['hasChild'] = '1';
                $menus[$k]['childMenu'] = $childMenu;
            } else {
                $menus[$k]['hasChild'] = '0';
                $menus[$k]['childMenu'] = [];
            }
        }
        return $menus;
    }

}
