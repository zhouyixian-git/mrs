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
use PHPExcel;
use PHPExcel_IOFactory;

class Order extends Base
{

    /**
     * 订单列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $order_sn = $request->param('order_sn');
        $order_status = $request->param('order_status');
        $where = [];
        if (!empty($order_sn)) {
            $where[] = ['t1.order_sn', 'like', "%$order_sn%"];
        }
        if (!empty($order_status)) {
            $where[] = ['t1.order_status', '=', $order_status];
        }

        $domain = config('domain');
        $orderList = Db::table('mrs_orders')
            ->alias('t1')
            ->field('t1.*')
            ->where($where)
            ->order('t1.create_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page'])
            ->each(function ($order, $key) use ($domain) {
                $where = [];
                $where[] = ['order_id', '=', $order['order_id']];
                $goodsList = Db::table('mrs_order_goods')
                    ->field('goods_name,goods_num,goods_m_list_image,goods_price')
                    ->where($where)->select();

                foreach ($goodsList as $k1 => $v1) {
                    $goodsList[$k1]['goods_m_list_image'] = $domain . $v1['goods_m_list_image'];
                }
                $order['goodsList'] = $goodsList;
                return $order;
            });

        $this->assign('order_status', $order_status);
        $this->assign('order_sn', $order_sn);
        $this->assign('orderList', $orderList);
        return $this->fetch();
    }

    /**
     * 订单详情
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function view(Request $request)
    {
        $order_id = $request->get('order_id');
        if (empty($order_id)) {
            $this->error('缺少关键参数order_id');
        }

        //订单信息
        $order = Db::table('mrs_orders')->where('order_id', '=', $order_id)->find();
        if (!$order) {
            $this->error('没有找到对应的订单信息');
        }

        //订单商品信息
        $orderGoods = Db::table('mrs_order_goods')->where('order_id', '=', $order_id)->select();
        foreach ($orderGoods as $k => $v) {
            $goods_sku = '';
            if (!empty($v['sku_json'])) {
                $skuJson = json_decode(json_decode($v['sku_json'], true), true);
                if (!empty($skuJson)) {
                    foreach ($skuJson as $key => $value) {
                        $goods_sku .= $value['sku_name'] . '-';
                    }
                }
                $goods_sku = substr($goods_sku, 0, -1);
            }
            $orderGoods[$k]['goods_sku'] = $goods_sku;
        }

        //订单动作信息
        $orderAction = Db::table('mrs_order_action')->where('order_id', '=', $order_id)->order('create_time desc')->select();

        $order['orderGoods'] = $orderGoods;
        $order['orderAction'] = $orderAction;

        $this->assign('order', $order);
        return $this->fetch();
    }

    /**
     * 订单发货
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function shipping(Request $request)
    {
        if ($request->isPost()) {
            $order_id = $request->post('order_id');
            $data['courier_company'] = $request->post('courier_company');
            $data['courier_number'] = $request->post('courier_number');

            if (empty($order_id)) {
                echo $this->errorJson(0, '缺少关键参数order_id');
                exit;
            }
            if (empty($data['courier_company'])) {
                echo $this->errorJson(0, '请填写快递公司信息');
                exit;
            }
            if (empty($data['courier_number'])) {
                echo $this->errorJson(0, '请填写快递单号信息');
                exit;
            }

            $data['order_status'] = 3;
            $data['shipping_status'] = 2;
            $data['shipping_time'] = time();
            Db::table('mrs_orders')->where('order_id', '=', $order_id)->update($data);

            echo $this->successJson();
            exit;
        }

        $order_id = $request->get('order_id');
        if (empty($order_id)) {
            $this->error('缺少关键参数order_id');
        }

        $this->assign('order_id', $order_id);
        return $this->fetch();
    }

    /**
     * 订单退款
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refund(Request $request)
    {
        if ($request->isPost()) {
            $order_id = $request->post('order_id');
            $refund_status = $request->post('refund_status');
            $refuse_reason = $request->post('refuse_reason');

            if (empty($order_id)) {
                echo $this->errorJson(0, '缺少关键参数order_id');
                exit;
            }

            if ($refund_status == 4 && empty($refuse_reason)) {
                echo $this->errorJson(0, '请填写拒绝退款理由');
                exit;
            }

            $order = Db::table('mrs_orders')
                ->where('order_id', '=', $order_id)
                ->find();

            if (empty($order)) {
                echo $this->errorJson(0, '没有找到对应的订单信息');
                exit;
            }

            if ($refund_status == 4) { //卖家拒绝退款
                $data['refund_status'] = 4;
                $data['refuse_reason'] = $refuse_reason;
                Db::table('mrs_orders')->where('order_id', '=', $order_id)->update($data);

                echo $this->successJson();
                exit;
            }

            //调用微信支付退款接口
            $out_refund_no = 'LZHS' . date('YmdHis', time()) . rand(100000, 999999);
            $wechatModel = new \app\admin\model\Wechat();
            $data['out_trade_no'] = $order['pay_order_sn'];
            $data['out_refund_no'] = $out_refund_no;
            $data['amount'] = $order['cash_amount'];
            $result = $wechatModel->refund($data);
            if ($result['errcode'] == 1) {
                echo $this->errorJson(0, $result['errmsg']);
                exit;
            } else {
                //调用退款查询接口
                $refundResult = $wechatModel->queryRefundOrder($out_refund_no);
                if ($refundResult['errcode'] == 1) { //退款成功
                    //todo 更新订单表信息、订单动作表、订单退款记录表
                    //更新订单表数据
                    Db::startTrans();
                    try {
                        $orderData['order_status'] = 5;
                        $orderData['refund_status'] = 5;
                        $orderData['refund_order_sn'] = $out_refund_no;
                        $orderData['refund_time'] = time();
                        $res1 = Db::table('mrs_orders')
                            ->where('order_id', '=', $order_id)
                            ->update($orderData);

                        //生成订单动作表
                        $actionData['order_id'] = $order_id;
                        $actionData['action_name'] = '管理员退款操作';
                        $actionData['action_user_id'] = parent::$_ADMINID;
                        $actionData['action_user_name'] = parent::$_ADMINNAME;
                        $actionData['action_remark'] = '管理员【' . parent::$_ADMINNAME . '】处理用户退款';
                        $actionData['create_time'] = time();
                        $res2 = Db::table('mrs_order_action')->insert($actionData);

                        //生成退款记录
                        $refundData['order_id'] = $order_id;
                        $refundData['money'] = $order['cash_amount'];
                        $refundData['pay_time'] = time();
                        $refundData['is_pay'] = 1;
                        $refundData['wc_order_id'] = $out_refund_no;
                        $res3 = Db::table('mrs_order_refund_record')->insert($refundData);

                        if ($res1 && $res2 && $res3) {
                            Db::commit();
                            echo $this->successJson();
                            exit;
                        } else {
                            Db::rollback();
                            echo $this->errorJson(0, '退款成功,更新订单数据失败');
                            exit;
                        }

                    } catch (Exception $e) {
                        Db::rollback();
                        echo $this->errorJson(0, '退款成功,更新订单数据异常');
                        exit;
                    }
                }
            }
        }

        $order_id = $request->get('order_id');
        if (empty($order_id)) {
            $this->error('缺少关键参数order_id');
        }

        $this->assign('order_id', $order_id);
        return $this->fetch();
    }

    /**
     * 订单退货
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function sales(Request $request)
    {
        if ($request->isPost()) {
            $order_id = $request->post('order_id');
            $sales_status = $request->post('sales_status');
            $refuse_reason = $request->post('refuse_reason');

            if (empty($order_id)) {
                echo $this->errorJson(0, '缺少关键参数order_id');
                exit;
            }

            if ($sales_status == 4 && empty($refuse_reason)) {
                echo $this->errorJson(0, '请填写拒绝退货理由');
                exit;
            }

            $order = Db::table('mrs_orders')
                ->where('order_id', '=', $order_id)
                ->find();

            if (empty($order)) {
                echo $this->errorJson(0, '没有找到对应的订单信息');
                exit;
            }

            if ($sales_status == 4) { //卖家拒绝退货
                $data['sales_status'] = 4;
                $data['refuse_reason'] = $refuse_reason;
                Db::table('mrs_orders')->where('order_id', '=', $order_id)->update($data);

                echo $this->successJson();
                exit;
            }

            //调用微信支付退款接口
            $out_refund_no = 'LZHS' . date('YmdHis', time()) . rand(100000, 999999);
            $wechatModel = new \app\admin\model\Wechat();
            $data['out_trade_no'] = $order['pay_order_sn'];
            $data['out_refund_no'] = $out_refund_no;
            $data['amount'] = $order['cash_amount'];
            $result = $wechatModel->refund($data);
            if ($result['errcode'] == 1) {
                echo $this->errorJson(0, $result['errmsg']);
                exit;
            } else {
                //调用退款查询接口
                $refundResult = $wechatModel->queryRefundOrder($out_refund_no);
                if ($refundResult['errcode'] == 1) { //退款成功
                    //todo 更新订单表信息、订单动作表、订单退款记录表
                    //更新订单表数据
                    Db::startTrans();
                    try {
                        $orderData['order_status'] = 5;
                        $orderData['sales_status'] = 5;
                        $orderData['refund_order_sn'] = $out_refund_no;
                        $orderData['refund_time'] = time();
                        $res1 = Db::table('mrs_orders')
                            ->where('order_id', '=', $order_id)
                            ->update($orderData);

                        //生成订单动作表
                        $actionData['order_id'] = $order_id;
                        $actionData['action_name'] = '管理员退货操作';
                        $actionData['action_user_id'] = parent::$_ADMINID;
                        $actionData['action_user_name'] = parent::$_ADMINNAME;
                        $actionData['action_remark'] = '管理员【' . parent::$_ADMINNAME . '】处理用户退货';
                        $actionData['create_time'] = time();
                        $res2 = Db::table('mrs_order_action')->insert($actionData);

                        //生成退款记录
                        $refundData['order_id'] = $order_id;
                        $refundData['money'] = $order['cash_amount'];
                        $refundData['pay_time'] = time();
                        $refundData['is_pay'] = 1;
                        $refundData['wc_order_id'] = $out_refund_no;
                        $res3 = Db::table('mrs_order_refund_record')->insert($refundData);

                        if ($res1 && $res2 && $res3) {
                            Db::commit();
                            echo $this->successJson();
                            exit;
                        } else {
                            Db::rollback();
                            echo $this->errorJson(0, '退货成功,更新订单数据失败');
                            exit;
                        }

                    } catch (Exception $e) {
                        Db::rollback();
                        echo $this->errorJson(0, '退货成功,更新订单数据异常');
                        exit;
                    }
                }
            }
        }

        $order_id = $request->get('order_id');
        if (empty($order_id)) {
            $this->error('缺少关键参数order_id');
        }

        $this->assign('order_id', $order_id);
        return $this->fetch();
    }

    /**
     * 订单导出
     * @param Request $request
     */
    public function expert(Request $request)
    {
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);

        $objPHPExcel->getActiveSheet()->getDefaultRowDimension()->setRowHeight(30);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        // 设置列的宽度
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('O')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('R')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('S')->setWidth(25);

        // 设置表头
        $objPHPExcel->getActiveSheet()->SetCellValue('A1', '订单号');
        $objPHPExcel->getActiveSheet()->SetCellValue('B1', '用户名称');
        $objPHPExcel->getActiveSheet()->SetCellValue('C1', '商品名称');
        $objPHPExcel->getActiveSheet()->SetCellValue('D1', '商品数量');
        $objPHPExcel->getActiveSheet()->SetCellValue('E1', '商品价格');
        $objPHPExcel->getActiveSheet()->SetCellValue('F1', '订单状态');
        $objPHPExcel->getActiveSheet()->SetCellValue('G1', '支付状态');
        $objPHPExcel->getActiveSheet()->SetCellValue('H1', '发货状态');
        $objPHPExcel->getActiveSheet()->SetCellValue('I1', '退款状态');
        $objPHPExcel->getActiveSheet()->SetCellValue('J1', '退货状态');
        $objPHPExcel->getActiveSheet()->SetCellValue('K1', '支付方式');
        $objPHPExcel->getActiveSheet()->SetCellValue('L1', '订单金额');
        $objPHPExcel->getActiveSheet()->SetCellValue('M1', '积分金额');
        $objPHPExcel->getActiveSheet()->SetCellValue('N1', '现金金额');
        $objPHPExcel->getActiveSheet()->SetCellValue('O1', '创建时间');
        $objPHPExcel->getActiveSheet()->SetCellValue('P1', '支付时间');
        $objPHPExcel->getActiveSheet()->SetCellValue('Q1', '发货时间');
        $objPHPExcel->getActiveSheet()->SetCellValue('R1', '订单取消时间');
        $objPHPExcel->getActiveSheet()->SetCellValue('S1', '确认收货时间');

        //查询订单数据
        $order_sn = $request->param('order_sn');
        $order_status = $request->param('order_status');
        $where = [];
        if (!empty($order_sn)) {
            $where[] = ['t1.order_sn', 'like', "%$order_sn%"];
        }
        if (!empty($order_status)) {
            $where[] = ['t1.order_status', '=', $order_status];
        }

        $orderList = Db::table('mrs_orders')
            ->alias('t1')
            ->field('t1.*')
            ->where($where)
            ->order('t1.create_time desc')
            ->select();

        //存取数据
        $num = 2;
        foreach ($orderList as $k => $v) {
            $objPHPExcel->getActiveSheet()->SetCellValue('A' . $num, ' ' . $v['order_sn']); //防止订单号过长变成科学计算问题所以在订单号前拼接空字符，转化为字符串。 ' '.$v['order_no']
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $num, $v['user_name']);

            $order_status_remark = '未知';
            if ($v['order_status'] == 1) {
                $order_status_remark = '待付款';
            } else if ($v['order_status'] == 2) {
                $order_status_remark = '待发货';
            } else if ($v['order_status'] == 3) {
                $order_status_remark = '已发货';
            } else if ($v['order_status'] == 4) {
                $order_status_remark = '已收货';
            } else if ($v['order_status'] == 5) {
                $order_status_remark = '已取消';
            }
            $objPHPExcel->getActiveSheet()->SetCellValue('F' . $num, $order_status_remark);
            $pay_status_remark = '未知';
            if ($v['pay_status'] == 1) {
                $pay_status_remark = '未付款';
            } else if ($v['pay_status'] == 2) {
                $pay_status_remark = '已付款';
            }
            $objPHPExcel->getActiveSheet()->SetCellValue('G' . $num, $pay_status_remark);
            $shipping_status_remark = '未知';
            if ($v['pay_status'] == 1) {
                $shipping_status_remark = '未发货';
            } else if ($v['pay_status'] == 2) {
                $shipping_status_remark = '已发货';
            } else if ($v['pay_status'] == 3) {
                $shipping_status_remark = '已收货';
            }
            $objPHPExcel->getActiveSheet()->SetCellValue('H' . $num, $shipping_status_remark);
            $refund_status_remark = '未知';
            if ($v['refund_status'] == 1) {
                $refund_status_remark = '没有退款';
            } else if ($v['refund_status'] == 2) {
                $refund_status_remark = '买家申请退款';
            } else if ($v['refund_status'] == 3) {
                $refund_status_remark = '退款中';
            } else if ($v['refund_status'] == 4) {
                $refund_status_remark = '卖家拒绝退款';
            } else if ($v['refund_status'] == 5) {
                $refund_status_remark = '退款成功';
            }
            $objPHPExcel->getActiveSheet()->SetCellValue('I' . $num, $refund_status_remark);
            $sales_status_remark = '未知';
            if ($v['sales_status'] == 1) {
                $sales_status_remark = '没有退货';
            } else if ($v['sales_status'] == 2) {
                $sales_status_remark = '买家申请退货';
            } else if ($v['sales_status'] == 3) {
                $sales_status_remark = '退货中';
            } else if ($v['sales_status'] == 4) {
                $sales_status_remark = '卖家拒绝退货';
            } else if ($v['sales_status'] == 5) {
                $sales_status_remark = '退货成功';
            }
            $objPHPExcel->getActiveSheet()->SetCellValue('J' . $num, $sales_status_remark);
            $pay_type_remark = '未知';
            if ($v['pay_type'] == 1) {
                $pay_type_remark = '微信支付';
            } else if ($v['pay_type'] == 2) {
                $pay_type_remark = '积分+微信支付';
            }
            $objPHPExcel->getActiveSheet()->SetCellValue('K' . $num, $pay_type_remark);
            $objPHPExcel->getActiveSheet()->SetCellValue('L' . $num, $v['order_amount']);
            $objPHPExcel->getActiveSheet()->SetCellValue('M' . $num, $v['integral_amount']);
            $objPHPExcel->getActiveSheet()->SetCellValue('N' . $num, $v['cash_amount']);
            $objPHPExcel->getActiveSheet()->SetCellValue('O' . $num, empty($v['create_time']) ? '-' : date('Y-m-d H:i', $v['create_time']));
            $objPHPExcel->getActiveSheet()->SetCellValue('P' . $num, empty($v['pay_time']) ? '-' : date('Y-m-d H:i', $v['pay_time']));
            $objPHPExcel->getActiveSheet()->SetCellValue('Q' . $num, empty($v['shipping_time']) ? '-' : date('Y-m-d H:i', $v['shipping_time']));
            $objPHPExcel->getActiveSheet()->SetCellValue('R' . $num, empty($v['cancel_time']) ? '-' : date('Y-m-d H:i', $v['cancel_time']));
            $objPHPExcel->getActiveSheet()->SetCellValue('S' . $num, empty($v['confirm_time']) ? '-' : date('Y-m-d H:i', $v['confirm_time']));

            $goodsList = Db::table('mrs_order_goods')
                ->field('goods_name,goods_num,goods_price')
                ->where('order_id', '=', $v['order_id'])->select();
            foreach ($goodsList as $k1 => $v1) {
                $objPHPExcel->getActiveSheet()->SetCellValue('C' . $num, $v1['goods_name']);
                $objPHPExcel->getActiveSheet()->SetCellValue('D' . $num, $v1['goods_num']);
                $objPHPExcel->getActiveSheet()->SetCellValue('E' . $num, $v1['goods_price']);
                $num++;
            }

            if(count($goodsList) > 1){
                $start = $num - count($goodsList);
                $end = $num - 1;
                $objPHPExcel->setActiveSheetIndex(0)
                    ->mergeCells('A' . $start . ':A' . $end)
                    ->mergeCells('B' . $start . ':B' . $end)
                    ->mergeCells('F' . $start . ':F' . $end)
                    ->mergeCells('G' . $start . ':G' . $end)
                    ->mergeCells('H' . $start . ':H' . $end)
                    ->mergeCells('I' . $start . ':I' . $end)
                    ->mergeCells('L' . $start . ':L' . $end)
                    ->mergeCells('M' . $start . ':M' . $end)
                    ->mergeCells('N' . $start . ':N' . $end)
                    ->mergeCells('O' . $start . ':O' . $end)
                    ->mergeCells('P' . $start . ':P' . $end)
                    ->mergeCells('Q' . $start . ':Q' . $end)
                    ->mergeCells('R' . $start . ':R' . $end)
                    ->mergeCells('S' . $start . ':S' . $end);
            }
        }

        $fileName = "订单信息" . time();
        $xlsName = iconv('utf-8', 'gb2312', $fileName);
        $objPHPExcel->getActiveSheet()->setTitle('订单列表'); // 设置工作表名
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel); //下载 excel5与excel2007
        ob_end_clean(); // 清除缓冲区,避免乱码
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate,post-check=0,pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl;charset=UTF-8");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header("Content-Disposition:attachment;filename=" . $xlsName . ".xls");
        header("Content-Transfer-Encoding:binary");
        $objWriter->save("php://output");
    }

}
