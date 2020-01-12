<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/12 0012
 * Time: 21:55
 */

namespace app\api\controller;


use think\Db;
use think\Request;

class Recovery extends Base
{

    public function nearsite(Request $request)
    {
        /*echo $this->getDistance('113.269068','23.119355','113.953166','22.549693',1, 2);
        exit;*/
        if ($request->isPost()) {
            $lng = $request->post('lng');
            $lat = $request->post('lat');
            $key_word = $request->post('key_word');
            $page = $request->post('page');

            if (empty($lng)) {
                echo $this->errorJson(1, '缺少关键数据lng');
                exit;
            }
            if (empty($lat)) {
                echo $this->errorJson(1, '缺少关键数据lat');
                exit;
            }

            if (empty($page)) {
                $page = 1;
            }
            $pageSize = 10;

            $where = [];
            if (!empty($key_word)) {
                $where[] = [
                    ['t1.site_address', 'like', "%$key_word%"]
                ];
            }

            $siteList = Db::table('mrs_site')
                ->alias('t1')
                ->field("t1.site_id,t1.site_name,t1.lng,t1.lat,t1.site_address,
                    ROUND(
                        6378.138 * 2 * ASIN(
                            SQRT(
                                POW(
                                    SIN(
                                        (
                                            $lat * PI() / 180 - t1.lat * PI() / 180
                                        ) / 2
                                    ),
                                    2
                                ) + COS($lat * PI() / 180) * COS(t1.lat * PI() / 180) * POW(
                                    SIN(
                                        (
                                            $lng * PI() / 180 - t1.lng * PI() / 180
                                        ) / 2
                                    ),
                                    2
                                )
                            )
                        ) * 1000
                    ) AS distance
                ")
                ->where($where)
                ->limit(($page - 1) * $pageSize, $pageSize)
                ->order('distance asc')
                ->select();

            $totalCount = Db::table('mrs_site')
                ->where($where)
                ->count();

            if ($siteList) {
                $total_page = ceil($totalCount / $pageSize);

                $data['siteList'] = $siteList;
                $data['total_page'] = $total_page;
                echo $this->successJson($data);
                exit;
            } else {
                echo $this->errorJson(1, '没有站点信息');
                exit;
            }
        }
    }



    /**
     * 计算两点地理坐标之间的距离
     * @param  Decimal $longitude1 起点经度
     * @param  Decimal $latitude1  起点纬度
     * @param  Decimal $longitude2 终点经度
     * @param  Decimal $latitude2  终点纬度
     * @param  Int     $unit       单位 1:米 2:公里
     * @param  Int     $decimal    精度 保留小数位数
     * @return Decimal
     */
    function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit=2, $decimal=2){

        $EARTH_RADIUS = 6370.996; // 地球半径系数
        $PI = 3.1415926;

        $radLat1 = $latitude1 * $PI / 180.0;
        $radLat2 = $latitude2 * $PI / 180.0;

        $radLng1 = $longitude1 * $PI / 180.0;
        $radLng2 = $longitude2 * $PI /180.0;

        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        $distance = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
        $distance = $distance * $EARTH_RADIUS * 1000;

        if($unit==2){
            $distance = $distance / 1000;
        }

        return round($distance, $decimal);

    }

}
