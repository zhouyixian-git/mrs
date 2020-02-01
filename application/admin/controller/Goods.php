<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/12/10 0010
 * Time: 22:23
 */

namespace app\admin\controller;


use think\Db;
use think\Request;

class Goods extends Base
{

    /**
     * 商品列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $goods_name = $request->param('goods_name');
        $cate_id = $request->param('cate_id');
        $where = [];
        if (!empty($goods_name)) {
            $where[] = ['goods_name', 'like', "%$goods_name%"];
        }
        if (!empty($cate_id)) {
            $where[] = ['cate_id', '=', $cate_id];
        }

        $goodsList = Db::table('mrs_goods')
            ->where($where)
            ->order('create_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $page = $goodsList->render();
        $count = Db::table('mrs_goods')->where($where)->count();

        $goodsCateList = \app\admin\model\GoodsCate::where('is_actived', 1)->order('order_no asc,create_time desc')->select();
        $this->assign('goodsCateList', $goodsCateList);
        $this->assign('cate_id', $cate_id);
        $this->assign('goods_name', $goods_name);
        $this->assign('page', $page);
        $this->assign('count', $count);
        $this->assign('goodsList', $goodsList);
        return $this->fetch();
    }

    /**
     * 添加商品
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data['cate_id'] = $request->post('cate_id');
            $data['cate_name'] = $request->post('cate_name');
            $data['goods_name'] = $request->post('goods_name');
            $data['goods_desc'] = $request->post('goods_desc');
            $data['goods_detail'] = $request->post('goods_detail');
            $data['goods_price'] = $request->post('goods_price');
            $data['is_top'] = $request->post('is_top');
            $data['is_cash'] = $request->post('is_cash');
            $data['max_cash'] = $request->post('max_cash');
            $data['goods_status'] = $request->post('goods_status');
            $goods_image_count = $request->post('goods_image_count'); //商品图片数量

            $validate = new \app\admin\validate\Goods();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $goodsImageList = [];
            for ($i = 0; $i < $goods_image_count; $i++) {
                $goodsImage = $request->post("goodsImage_" . ($i + 1));
                if (empty($goodsImage)) {
                    continue;
                }
                $goodsImageList[] = $goodsImage;
            }
            if (empty($goodsImageList)) {
                echo $this->errorJson(0, '请先上传商品图片');
                return;
            }

            $data['goods_img'] = $goodsImageList[0];
            $data['create_time'] = time();
            $data['cteate_admin_id'] = parent::$loginAdmin['admin_id'];

            Db::startTrans();
            try {
                //添加商品信息
                Db::table('mrs_goods')->insert($data);
                $goods_id = Db::getLastInsID();

                //添加商品图片信息
                $goods_img = [];
                foreach ($goodsImageList as $k => $v) {
                    $goods_img[] = [
                        'img_path' => $v,
                        'goods_id' => $goods_id,
                        'order_no' => $k + 1,
                        'create_time' => time()
                    ];
                }
                Db::table('mrs_goods_img')->insertAll($goods_img);

                //添加商品规格信息
                $pCount = $request->post('pCount');
                if ($pCount == 1) { //单规格
                    $pSkuId = $request->post('pSkuId');
                    $pSkuName = $request->post('pSkuName');
                    $cSkuCount = $request->post('cSkuCount');

                    for ($i = 0; $i < $cSkuCount; $i++) {
                        $skuGroupData = [];
                        $cSkuId = $request->post('cSkuId_' . ($i + 1));
                        $cSkuName = $request->post('cSkuName_' . ($i + 1));
                        $shopPrice = $request->post('shop_price_' . $cSkuId);
                        $goodsStock = $request->post('goods_stock_' . $cSkuId);

                        $skuGroupData['goods_id'] = $goods_id;
                        $skuGroupData['parent_sku_id'] = $pSkuId;
                        $skuGroupData['parent_sku_name'] = $pSkuName;
                        $skuGroupData['sku_name'] = $cSkuName;
                        $skuGroupData['sku_id'] = $cSkuId;
                        $skuGroupData['order_no'] = $i + 1;
                        Db::table('mrs_goods_sku_group')->insert($skuGroupData);

                        $childJson['cSkuId'] = $cSkuId;
                        $childJson['cSkuName'] = $cSkuName;

                        $parentJson[0]['pSkuId'] = $pSkuId;
                        $parentJson[0]['pSkuName'] = $pSkuName;
                        $parentJson[0]['child'] = $childJson;

                        $skuJson['parentSku'] = $parentJson;
                        $skuJson['shopPrice'] = $shopPrice;
                        $skuJson['goodsStock'] = $goodsStock;
                        $skuJson['unionId'] = $cSkuId;

                        $skuInfo['skuInfo'] = $skuJson;
                        $skuDetailData = [];
                        $skuDetailData['goods_id'] = $goods_id;
                        $skuDetailData['sku_json'] = json_encode($skuInfo);
                        $skuDetailData['shop_price'] = $shopPrice;
                        $skuDetailData['goods_stock'] = $goodsStock;
                        $skuDetailData['order_no'] = $i + 1;
                        Db::table('mrs_goods_sku_detail')->insert($skuDetailData);
                    }
                } else if ($pCount == 2) { //双规格
                    $firstPSkuId = $request->post("pSkuId_1");    //第一级父规格
                    $firstPSkuName = $request->post("pSkuName_1");//第一级父规格名称
                    $secondPSkuId = $request->post("pSkuId_2");    //第二级父规格
                    $secondPSkuName = $request->post("pSkuName_2");//第二级父规格名称

                    $firstCSkuCount = $request->post("cSkuCount_1");//第一级子规格数量
                    $secondCSkuCount = $request->post("cSkuCount_2");//第二级子规格数量

                    $orderNo = 0;
                    for ($i = 0; $i < $firstCSkuCount; $i++) {
                        $firstCSkuId = $request->post("firstCSkuId_" . ($i + 1));    //第一级子规格id
                        $firstCSkuName = $request->post("firstCSkuName_" . ($i + 1));    //第一级子规格name

                        $skuGroupData = [];
                        $skuGroupData['goods_id'] = $goods_id;
                        $skuGroupData['parent_sku_id'] = $firstPSkuId;
                        $skuGroupData['parent_sku_name'] = $firstPSkuName;
                        $skuGroupData['sku_name'] = $firstCSkuName;
                        $skuGroupData['sku_id'] = $firstCSkuId;
                        $skuGroupData['order_no'] = $i + 1;
                        Db::table('mrs_goods_sku_group')->insert($skuGroupData);

                        for ($j = 0; $j < $secondCSkuCount; $j++) {
                            $orderNo++;
                            $secondCSkuId = $request->post("secondCSkuId_" . ($j + 1));    //第二级子规格id
                            $secondCSkuName = $request->post("secondCSkuName_" . ($j + 1));    //第二级子规格name
                            if ($i == 0) {
                                $skuGroupData = [];
                                $skuGroupData['goods_id'] = $goods_id;
                                $skuGroupData['parent_sku_id'] = $secondPSkuId;
                                $skuGroupData['parent_sku_name'] = $secondPSkuName;
                                $skuGroupData['sku_name'] = $secondCSkuName;
                                $skuGroupData['sku_id'] = $secondCSkuId;
                                $skuGroupData['order_no'] = $j + 1;
                                Db::table('mrs_goods_sku_group')->insert($skuGroupData);
                            }

                            $shopPrice = $request->post("shop_price_" . $firstCSkuId . "_" . $secondCSkuId);
                            $goodsStock = $request->post("goods_stock_" . $firstCSkuId . "_" . $secondCSkuId);

                            $childJson['cSkuId'] = $firstCSkuId;
                            $childJson['cSkuName'] = $firstCSkuName;
                            $parentJson[0]['pSkuId'] = $firstPSkuId;
                            $parentJson[0]['pSkuName'] = $firstPSkuName;
                            $parentJson[0]['child'] = $childJson;

                            $childJson['cSkuId'] = $secondCSkuId;
                            $childJson['cSkuName'] = $secondCSkuName;
                            $parentJson[1]['pSkuId'] = $secondPSkuId;
                            $parentJson[1]['pSkuName'] = $secondPSkuName;
                            $parentJson[1]['child'] = $childJson;

                            $skuJson['parentSku'] = $parentJson;
                            $skuJson['shopPrice'] = $shopPrice;
                            $skuJson['goodsStock'] = $goodsStock;
                            $skuJson['unionId'] = $firstCSkuId . '_' . $secondCSkuId;

                            $skuInfo['skuInfo'] = $skuJson;
                            $skuDetailData = [];
                            $skuDetailData['goods_id'] = $goods_id;
                            $skuDetailData['sku_json'] = json_encode($skuInfo);
                            $skuDetailData['shop_price'] = $shopPrice;
                            $skuDetailData['goods_stock'] = $goodsStock;
                            $skuDetailData['order_no'] = $orderNo;
                            Db::table('mrs_goods_sku_detail')->insert($skuDetailData);
                        }
                    }
                } else if ($pCount == 3) {
                    $firstPSkuId = $request->post("pSkuId_1");    //第一级父规格
                    $firstPSkuName = $request->post("pSkuName_1");//第一级父规格名称
                    $secondPSkuId = $request->post("pSkuId_2");    //第二级父规格
                    $secondPSkuName = $request->post("pSkuName_2");//第二级父规格名称
                    $thirdPSkuId = $request->post("pSkuId_3");    //第三级父规格
                    $thirdPSkuName = $request->post("pSkuName_3");//第三级父规格名称

                    $firstCSkuCount = $request->post("cSkuCount_1");//第一级子规格数量
                    $secondCSkuCount = $request->post("cSkuCount_2");//第二级子规格数量
                    $thirdCSkuCount = $request->post("cSkuCount_3");//第三级子规格数量

                    $orderNo = 0;
                    for ($i = 0; $i < $firstCSkuCount; $i++) {
                        $firstCSkuId = $request->post("firstCSkuId_" . ($i + 1));    //第一级子规格id
                        $firstCSkuName = $request->post("firstCSkuName_" . ($i + 1));    //第一级子规格name

                        $skuGroupData = [];
                        $skuGroupData['goods_id'] = $goods_id;
                        $skuGroupData['parent_sku_id'] = $firstPSkuId;
                        $skuGroupData['parent_sku_name'] = $firstPSkuName;
                        $skuGroupData['sku_name'] = $firstCSkuName;
                        $skuGroupData['sku_id'] = $firstCSkuId;
                        $skuGroupData['order_no'] = $i + 1;
                        Db::table('mrs_goods_sku_group')->insert($skuGroupData);

                        for ($j = 0; $j < $secondCSkuCount; $j++) {
                            $secondCSkuId = $request->post("secondCSkuId_" . ($j + 1));    //第二级子规格id
                            $secondCSkuName = $request->post("secondCSkuName_" . ($j + 1));    //第二级子规格name
                            if ($i == 0) {
                                $skuGroupData = [];
                                $skuGroupData['goods_id'] = $goods_id;
                                $skuGroupData['parent_sku_id'] = $secondPSkuId;
                                $skuGroupData['parent_sku_name'] = $secondPSkuName;
                                $skuGroupData['sku_name'] = $secondCSkuName;
                                $skuGroupData['sku_id'] = $secondCSkuId;
                                $skuGroupData['order_no'] = $j + 1;
                                Db::table('mrs_goods_sku_group')->insert($skuGroupData);
                            }

                            for ($k = 0; $k < $thirdCSkuCount; $k++) {
                                $orderNo++;
                                $thirdCSkuId = $request->post("thirdCSkuId_" . ($k + 1));    //第三级子规格id
                                $thirdCSkuName = $request->post("thirdCSkuName_" . ($k + 1));    //第三级子规格name
                                if ($i == 0 && $j == 0) {
                                    $skuGroupData = [];
                                    $skuGroupData['goods_id'] = $goods_id;
                                    $skuGroupData['parent_sku_id'] = $thirdPSkuId;
                                    $skuGroupData['parent_sku_name'] = $thirdPSkuName;
                                    $skuGroupData['sku_name'] = $thirdCSkuName;
                                    $skuGroupData['sku_id'] = $thirdCSkuId;
                                    $skuGroupData['order_no'] = $j + 1;
                                    Db::table('mrs_goods_sku_group')->insert($skuGroupData);
                                }

                                $shopPrice = $request->post("shop_price_" . $firstCSkuId . "_" . $secondCSkuId . "_" . $thirdCSkuId);
                                $goodsStock = $request->post("goods_stock_" . $firstCSkuId . "_" . $secondCSkuId . "_" . $thirdCSkuId);

                                $childJson['cSkuId'] = $firstCSkuId;
                                $childJson['cSkuName'] = $firstCSkuName;
                                $parentJson[0]['pSkuId'] = $firstPSkuId;
                                $parentJson[0]['pSkuName'] = $firstPSkuName;
                                $parentJson[0]['child'] = $childJson;

                                $childJson['cSkuId'] = $secondCSkuId;
                                $childJson['cSkuName'] = $secondCSkuName;
                                $parentJson[1]['pSkuId'] = $secondPSkuId;
                                $parentJson[1]['pSkuName'] = $secondPSkuName;
                                $parentJson[1]['child'] = $childJson;

                                $childJson['cSkuId'] = $thirdCSkuId;
                                $childJson['cSkuName'] = $thirdCSkuName;
                                $parentJson[2]['pSkuId'] = $thirdPSkuId;
                                $parentJson[2]['pSkuName'] = $thirdPSkuName;
                                $parentJson[2]['child'] = $childJson;

                                $skuJson['parentSku'] = $parentJson;
                                $skuJson['shopPrice'] = $shopPrice;
                                $skuJson['goodsStock'] = $goodsStock;
                                $skuJson['unionId'] = $firstCSkuId . '_' . $secondCSkuId . '_' . $thirdCSkuId;

                                $skuInfo['skuInfo'] = $skuJson;
                                $skuDetailData = [];
                                $skuDetailData['goods_id'] = $goods_id;
                                $skuDetailData['sku_json'] = json_encode($skuInfo);
                                $skuDetailData['shop_price'] = $shopPrice;
                                $skuDetailData['goods_stock'] = $goodsStock;
                                $skuDetailData['order_no'] = $orderNo;
                                Db::table('mrs_goods_sku_detail')->insert($skuDetailData);
                            }
                        }
                    }
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                echo $this->errorJson(0, $e->getMessage());
                return;
            }

            echo $this->successJson();
            return;
        }

        $goodsCateList = \app\admin\model\GoodsCate::where('is_actived', 1)->order('order_no asc,create_time desc')->select();

        $where = [];
        $where[] = ['is_delete', '=', 2];
        $where[] = ['p_sku_id', '=', 0];
        $skuList = \app\admin\model\Sku::where($where)->order('order_no asc,create_time desc')->select();

        $this->assign('goodsCateList', $goodsCateList);
        $this->assign('skuList', $skuList);
        return $this->fetch();
    }

    /**
     * 编辑商品
     * @param Request $request
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit(Request $request)
    {
        if ($request->isPost()) {
            $goods_id = $request->post('goods_id');
            $data['cate_id'] = $request->post('cate_id');
            $data['cate_name'] = $request->post('cate_name');
            $data['goods_name'] = $request->post('goods_name');
            $data['goods_desc'] = $request->post('goods_desc');
            $data['goods_detail'] = $request->post('goods_detail');
            $data['goods_price'] = $request->post('goods_price');
            $data['is_top'] = $request->post('is_top');
            $data['is_cash'] = $request->post('is_cash');
            $data['max_cash'] = $request->post('max_cash');
            $data['goods_status'] = $request->post('goods_status');
            $goods_image_count = $request->post('goods_image_count'); //商品图片数量

            $validate = new \app\admin\validate\Goods();
            if (!$validate->check($data)) {
                echo $this->errorJson(0, $validate->getError());
                return;
            }

            $goodsImageList = [];
            for ($i = 0; $i < $goods_image_count; $i++) {
                $goodsImage = $request->post("goodsImage_" . ($i + 1));
                if (empty($goodsImage)) {
                    continue;
                }
                $goodsImageList[] = $goodsImage;
            }
            if (empty($goodsImageList)) {
                echo $this->errorJson(0, '请先上传商品图片');
                return;
            }

            $data['goods_img'] = $goodsImageList[0];

            Db::startTrans();
            try {
                //修改商品信息
                Db::table('mrs_goods')->where('goods_id', $goods_id)->update($data);

                //修改商品图片
                Db::table('mrs_goods_img')->where('goods_id', $goods_id)->delete();

                $goods_img = [];
                foreach ($goodsImageList as $k => $v) {
                    $goods_img[] = [
                        'img_path' => $v,
                        'goods_id' => $goods_id,
                        'order_no' => $k + 1,
                        'create_time' => time()
                    ];
                }
                Db::table('mrs_goods_img')->insertAll($goods_img);

                //删除商品规格
                Db::table('mrs_goods_sku_group')->where('goods_id', '=', $goods_id)->delete();
                Db::table('mrs_goods_sku_detail')->where('goods_id', '=', $goods_id)->delete();

                //添加商品规格信息
                $pCount = $request->post('pCount');
                if ($pCount == 1) { //单规格
                    $pSkuId = $request->post('pSkuId');
                    $pSkuName = $request->post('pSkuName');
                    $cSkuCount = $request->post('cSkuCount');

                    for ($i = 0; $i < $cSkuCount; $i++) {
                        $skuGroupData = [];
                        $cSkuId = $request->post('cSkuId_' . ($i + 1));
                        $cSkuName = $request->post('cSkuName_' . ($i + 1));
                        $shopPrice = $request->post('shop_price_' . $cSkuId);
                        $goodsStock = $request->post('goods_stock_' . $cSkuId);

                        $skuGroupData['goods_id'] = $goods_id;
                        $skuGroupData['parent_sku_id'] = $pSkuId;
                        $skuGroupData['parent_sku_name'] = $pSkuName;
                        $skuGroupData['sku_name'] = $cSkuName;
                        $skuGroupData['sku_id'] = $cSkuId;
                        $skuGroupData['order_no'] = $i + 1;
                        Db::table('mrs_goods_sku_group')->insert($skuGroupData);

                        $childJson['cSkuId'] = $cSkuId;
                        $childJson['cSkuName'] = $cSkuName;

                        $parentJson[0]['pSkuId'] = $pSkuId;
                        $parentJson[0]['pSkuName'] = $pSkuName;
                        $parentJson[0]['child'] = $childJson;

                        $skuJson['parentSku'] = $parentJson;
                        $skuJson['shopPrice'] = $shopPrice;
                        $skuJson['goodsStock'] = $goodsStock;
                        $skuJson['unionId'] = $cSkuId;

                        $skuInfo['skuInfo'] = $skuJson;
                        $skuDetailData = [];
                        $skuDetailData['goods_id'] = $goods_id;
                        $skuDetailData['sku_json'] = json_encode($skuInfo);
                        $skuDetailData['shop_price'] = $shopPrice;
                        $skuDetailData['goods_stock'] = $goodsStock;
                        $skuDetailData['order_no'] = $i + 1;
                        Db::table('mrs_goods_sku_detail')->insert($skuDetailData);
                    }
                } else if ($pCount == 2) { //双规格
                    $firstPSkuId = $request->post("pSkuId_1");    //第一级父规格
                    $firstPSkuName = $request->post("pSkuName_1");//第一级父规格名称
                    $secondPSkuId = $request->post("pSkuId_2");    //第二级父规格
                    $secondPSkuName = $request->post("pSkuName_2");//第二级父规格名称

                    $firstCSkuCount = $request->post("cSkuCount_1");//第一级子规格数量
                    $secondCSkuCount = $request->post("cSkuCount_2");//第二级子规格数量

                    $orderNo = 0;
                    for ($i = 0; $i < $firstCSkuCount; $i++) {
                        $firstCSkuId = $request->post("firstCSkuId_" . ($i + 1));    //第一级子规格id
                        $firstCSkuName = $request->post("firstCSkuName_" . ($i + 1));    //第一级子规格name

                        $skuGroupData = [];
                        $skuGroupData['goods_id'] = $goods_id;
                        $skuGroupData['parent_sku_id'] = $firstPSkuId;
                        $skuGroupData['parent_sku_name'] = $firstPSkuName;
                        $skuGroupData['sku_name'] = $firstCSkuName;
                        $skuGroupData['sku_id'] = $firstCSkuId;
                        $skuGroupData['order_no'] = $i + 1;
                        Db::table('mrs_goods_sku_group')->insert($skuGroupData);

                        for ($j = 0; $j < $secondCSkuCount; $j++) {
                            $orderNo++;
                            $secondCSkuId = $request->post("secondCSkuId_" . ($j + 1));    //第二级子规格id
                            $secondCSkuName = $request->post("secondCSkuName_" . ($j + 1));    //第二级子规格name
                            if ($i == 0) {
                                $skuGroupData = [];
                                $skuGroupData['goods_id'] = $goods_id;
                                $skuGroupData['parent_sku_id'] = $secondPSkuId;
                                $skuGroupData['parent_sku_name'] = $secondPSkuName;
                                $skuGroupData['sku_name'] = $secondCSkuName;
                                $skuGroupData['sku_id'] = $secondCSkuId;
                                $skuGroupData['order_no'] = $j + 1;
                                Db::table('mrs_goods_sku_group')->insert($skuGroupData);
                            }

                            $shopPrice = $request->post("shop_price_" . $firstCSkuId . "_" . $secondCSkuId);
                            $goodsStock = $request->post("goods_stock_" . $firstCSkuId . "_" . $secondCSkuId);

                            $childJson['cSkuId'] = $firstCSkuId;
                            $childJson['cSkuName'] = $firstCSkuName;
                            $parentJson[0]['pSkuId'] = $firstPSkuId;
                            $parentJson[0]['pSkuName'] = $firstPSkuName;
                            $parentJson[0]['child'] = $childJson;

                            $childJson['cSkuId'] = $secondCSkuId;
                            $childJson['cSkuName'] = $secondCSkuName;
                            $parentJson[1]['pSkuId'] = $secondPSkuId;
                            $parentJson[1]['pSkuName'] = $secondPSkuName;
                            $parentJson[1]['child'] = $childJson;

                            $skuJson['parentSku'] = $parentJson;
                            $skuJson['shopPrice'] = $shopPrice;
                            $skuJson['goodsStock'] = $goodsStock;
                            $skuJson['unionId'] = $firstCSkuId . '_' . $secondCSkuId;

                            $skuInfo['skuInfo'] = $skuJson;
                            $skuDetailData = [];
                            $skuDetailData['goods_id'] = $goods_id;
                            $skuDetailData['sku_json'] = json_encode($skuInfo);
                            $skuDetailData['shop_price'] = $shopPrice;
                            $skuDetailData['goods_stock'] = $goodsStock;
                            $skuDetailData['order_no'] = $orderNo;
                            Db::table('mrs_goods_sku_detail')->insert($skuDetailData);
                        }
                    }
                } else if ($pCount == 3) {
                    $firstPSkuId = $request->post("pSkuId_1");    //第一级父规格
                    $firstPSkuName = $request->post("pSkuName_1");//第一级父规格名称
                    $secondPSkuId = $request->post("pSkuId_2");    //第二级父规格
                    $secondPSkuName = $request->post("pSkuName_2");//第二级父规格名称
                    $thirdPSkuId = $request->post("pSkuId_3");    //第三级父规格
                    $thirdPSkuName = $request->post("pSkuName_3");//第三级父规格名称

                    $firstCSkuCount = $request->post("cSkuCount_1");//第一级子规格数量
                    $secondCSkuCount = $request->post("cSkuCount_2");//第二级子规格数量
                    $thirdCSkuCount = $request->post("cSkuCount_3");//第三级子规格数量

                    $orderNo = 0;
                    for ($i = 0; $i < $firstCSkuCount; $i++) {
                        $firstCSkuId = $request->post("firstCSkuId_" . ($i + 1));    //第一级子规格id
                        $firstCSkuName = $request->post("firstCSkuName_" . ($i + 1));    //第一级子规格name

                        $skuGroupData = [];
                        $skuGroupData['goods_id'] = $goods_id;
                        $skuGroupData['parent_sku_id'] = $firstPSkuId;
                        $skuGroupData['parent_sku_name'] = $firstPSkuName;
                        $skuGroupData['sku_name'] = $firstCSkuName;
                        $skuGroupData['sku_id'] = $firstCSkuId;
                        $skuGroupData['order_no'] = $i + 1;
                        Db::table('mrs_goods_sku_group')->insert($skuGroupData);

                        for ($j = 0; $j < $secondCSkuCount; $j++) {
                            $secondCSkuId = $request->post("secondCSkuId_" . ($j + 1));    //第二级子规格id
                            $secondCSkuName = $request->post("secondCSkuName_" . ($j + 1));    //第二级子规格name
                            if ($i == 0) {
                                $skuGroupData = [];
                                $skuGroupData['goods_id'] = $goods_id;
                                $skuGroupData['parent_sku_id'] = $secondPSkuId;
                                $skuGroupData['parent_sku_name'] = $secondPSkuName;
                                $skuGroupData['sku_name'] = $secondCSkuName;
                                $skuGroupData['sku_id'] = $secondCSkuId;
                                $skuGroupData['order_no'] = $j + 1;
                                Db::table('mrs_goods_sku_group')->insert($skuGroupData);
                            }

                            for ($k = 0; $k < $thirdCSkuCount; $k++) {
                                $orderNo++;
                                $thirdCSkuId = $request->post("thirdCSkuId_" . ($k + 1));    //第三级子规格id
                                $thirdCSkuName = $request->post("thirdCSkuName_" . ($k + 1));    //第三级子规格name
                                if ($i == 0 && $j == 0) {
                                    $skuGroupData = [];
                                    $skuGroupData['goods_id'] = $goods_id;
                                    $skuGroupData['parent_sku_id'] = $thirdPSkuId;
                                    $skuGroupData['parent_sku_name'] = $thirdPSkuName;
                                    $skuGroupData['sku_name'] = $thirdCSkuName;
                                    $skuGroupData['sku_id'] = $thirdCSkuId;
                                    $skuGroupData['order_no'] = $j + 1;
                                    Db::table('mrs_goods_sku_group')->insert($skuGroupData);
                                }

                                $shopPrice = $request->post("shop_price_" . $firstCSkuId . "_" . $secondCSkuId . "_" . $thirdCSkuId);
                                $goodsStock = $request->post("goods_stock_" . $firstCSkuId . "_" . $secondCSkuId . "_" . $thirdCSkuId);

                                $childJson['cSkuId'] = $firstCSkuId;
                                $childJson['cSkuName'] = $firstCSkuName;
                                $parentJson[0]['pSkuId'] = $firstPSkuId;
                                $parentJson[0]['pSkuName'] = $firstPSkuName;
                                $parentJson[0]['child'] = $childJson;

                                $childJson['cSkuId'] = $secondCSkuId;
                                $childJson['cSkuName'] = $secondCSkuName;
                                $parentJson[1]['pSkuId'] = $secondPSkuId;
                                $parentJson[1]['pSkuName'] = $secondPSkuName;
                                $parentJson[1]['child'] = $childJson;

                                $childJson['cSkuId'] = $thirdCSkuId;
                                $childJson['cSkuName'] = $thirdCSkuName;
                                $parentJson[2]['pSkuId'] = $thirdPSkuId;
                                $parentJson[2]['pSkuName'] = $thirdPSkuName;
                                $parentJson[2]['child'] = $childJson;

                                $skuJson['parentSku'] = $parentJson;
                                $skuJson['shopPrice'] = $shopPrice;
                                $skuJson['goodsStock'] = $goodsStock;
                                $skuJson['unionId'] = $firstCSkuId . '_' . $secondCSkuId . '_' . $thirdCSkuId;

                                $skuInfo['skuInfo'] = $skuJson;
                                $skuDetailData = [];
                                $skuDetailData['goods_id'] = $goods_id;
                                $skuDetailData['sku_json'] = json_encode($skuInfo);
                                $skuDetailData['shop_price'] = $shopPrice;
                                $skuDetailData['goods_stock'] = $goodsStock;
                                $skuDetailData['order_no'] = $orderNo;
                                Db::table('mrs_goods_sku_detail')->insert($skuDetailData);
                            }
                        }
                    }
                }

                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                echo $this->errorJson(0, $e->getMessage());
                return;
            }

            echo $this->successJson();
            return;
        }

        $goods_id = $request->get('goods_id');
        if (empty($goods_id)) {
            $this->error('关键数据错误');
        }
        $goods = \app\admin\model\Goods::where('goods_id', $goods_id)->find();
        $goodsCateList = \app\admin\model\GoodsCate::where('is_actived', 1)->order('order_no asc,create_time desc')->select();

        $where = [];
        $where[] = ['is_delete', '=', 2];
        $where[] = ['p_sku_id', '=', 0];
        $skuList = \app\admin\model\Sku::where($where)->order('order_no asc,create_time desc')->select();

        $goodsPSkuIdList = Db::table('mrs_goods_sku_group')->field('DISTINCT(parent_sku_id)')->where('goods_id', '=', $goods_id)->select();
        $goodsGroupList = Db::table('mrs_goods_sku_group')->where('goods_id', '=', $goods_id)->order('order_no asc,group_id asc')->select();
        $goodsSkuDetailList = Db::table('mrs_goods_sku_detail')->where('goods_id', '=', $goods_id)->order('order_no asc,detail_id asc')->select();

        $this->assign('goods', $goods);
        $this->assign('goodsCateList', $goodsCateList);
        $this->assign('skuList', $skuList);
        $this->assign('goodsPSkuIdList', $goodsPSkuIdList);
        $this->assign('goodsGroupList', $goodsGroupList);
        $this->assign('goodsSkuDetailList', $goodsSkuDetailList);
        return $this->fetch();
    }

    /**
     * 删除商品
     * @param Request $request
     */
    public function delete(Request $request)
    {
        if ($request->isPost()) {
            $goods_id = $request->post('goods_id');

            if (empty($goods_id)) {
                echo $this->errorJson(0, '关键数据错误');
                exit;
            }

            Db::startTrans();
            try {
                Db::table('mrs_goods_img')->where(['goods_id' => $goods_id])->delete();
                Db::table('mrs_goods')->where(['goods_id' => $goods_id])->delete();
                Db::table('mrs_goods_sku_group')->where(['goods_id' => $goods_id])->delete();
                Db::table('mrs_goods_sku_detail')->where(['goods_id' => $goods_id])->delete();
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
     * 获取商品图片列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getgoodsimg(Request $request)
    {
        if ($request->isPost()) {
            $goods_id = $request->post('goods_id');

            $goodsImageList = Db::table('mrs_goods_img')
                ->field('img_path,goods_id,order_no')
                ->where('goods_id', $goods_id)
                ->order('order_no asc,create_time desc')
                ->select();

            echo $this->successJson($goodsImageList);
            return;
        }
    }

    /**
     * 获取子规格列表
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getChildSku(Request $request)
    {
        if ($request->isPost()) {
            $p_sku_id = $request->post('p_sku_id');

            $where = [];
            $where[] = ['is_delete', '=', 2];
            $where[] = ['p_sku_id', '=', $p_sku_id];
            $cSkuList = \app\admin\model\Sku::where($where)->order('order_no asc,create_time desc')->select();
            echo $this->successJson($cSkuList);
            return;
        }
    }

}
