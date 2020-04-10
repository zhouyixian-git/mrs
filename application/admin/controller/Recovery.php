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

        if (!empty(self::$loginAdmin['roleRegion'])) {
            $where[] = ['t1.region_id', 'in', self::$loginAdmin['roleRegion']];
        }
        if (!empty($recovery_record_sn)) {
            $where[] = ['t1.recovery_record_sn', 'like', "%$recovery_record_sn%"];
        }
        if (!empty($site_name)) {
            $where[] = ['t1.site_name', 'like', "%$site_name%"];
        }
        if (!empty($phone_no)) {
            $where[] = ['t1.phone_no', 'like', "%$phone_no%"];
        }

        $recoveryList = Db::table('mrs_recovery_record')
            ->alias('t1')
            ->field('t1.*,t2.nick_name')
            ->leftJoin('mrs_user t2', 't1.user_id = t2.user_id')
            ->where($where)
            ->order('t1.recovery_time desc')
            ->paginate(8, false, ['query' => $request->param(), 'type' => 'page\Page', 'var_page' => 'page']);

        $this->assign('recovery_record_sn', $recovery_record_sn);
        $this->assign('phone_no', $phone_no);
        $this->assign('site_name', $site_name);
        $this->assign('recoveryList', $recoveryList);
        return $this->fetch();
    }

    public function detail(Request $request)
    {
        $record_id = $request->get('record_id');

        if (empty($record_id)) {
            exit('参数异常，请关闭后重试');
        }

        $detailList = Db::table('mrs_recovery_record_detail')->where('recovery_record_id', '=', $record_id)->select();

        $this->assign('detailList', $detailList);
        return $this->fetch();
    }

    /**
     * 回收清单导出
     * @param Request $request
     */
    public function export(Request $request)
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

        // 设置表头
        $objPHPExcel->getActiveSheet()->SetCellValue('A1', '单号');
        $objPHPExcel->getActiveSheet()->SetCellValue('B1', '用户昵称');
        $objPHPExcel->getActiveSheet()->SetCellValue('C1', '用户手机号');
        $objPHPExcel->getActiveSheet()->SetCellValue('D1', '回收获得积分');
        $objPHPExcel->getActiveSheet()->SetCellValue('E1', '总重量');
        $objPHPExcel->getActiveSheet()->SetCellValue('F1', '站点名称');
        $objPHPExcel->getActiveSheet()->SetCellValue('G1', '回收时间');

        //查询订单数据
        $recovery_record_sn = $request->param('recovery_record_sn');
        $phone_no = $request->param('phone_no');
        $site_name = $request->param('site_name');

        $where = [];
        if (!empty(self::$loginAdmin['roleRegion'])) {
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
            ->select();

        //存取数据
        $num = 2;
        foreach ($recoveryList as $k => $v) {
            $objPHPExcel->getActiveSheet()->SetCellValue('A' . $num, ' ' . $v['recovery_record_sn']); //防止订单号过长变成科学计算问题所以在订单号前拼接空字符，转化为字符串。 ' '.$v['order_no']
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $num, $v['nick_name']);
            $objPHPExcel->getActiveSheet()->SetCellValue('C' . $num, $v['phone_no']);
            $objPHPExcel->getActiveSheet()->SetCellValue('D' . $num, $v['total_integral']);
            $objPHPExcel->getActiveSheet()->SetCellValue('E' . $num, $v['total_weight']);
            $objPHPExcel->getActiveSheet()->SetCellValue('F' . $num, $v['site_name']);
            $objPHPExcel->getActiveSheet()->SetCellValue('G' . $num, empty($v['recovery_time']) ? '-' : date('Y-m-d H:i', $v['recovery_time']));

            $num++;
        }

        $fileName = "资源回收清单" . time();
        $xlsName = iconv('utf-8', 'gb2312', $fileName);
        $objPHPExcel->getActiveSheet()->setTitle('资源回收清单'); // 设置工作表名
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
