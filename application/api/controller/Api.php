<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/5 0005
 * Time: 20:12
 */

namespace app\api\controller;


use think\Db;
use think\Request;

class Api extends Base
{
    /**
     * 第三方接口参数获取
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getApiParam(Request $request)
    {
        if ($request->isPost()) {
            $api_code = $request->post('api_code');
            if (empty($api_code)) {
                echo $this->errorJson('1', '缺少关键参数api_code');
                exit;
            }

            $api = Db::table("mrs_api")->where('api_code', '=', $api_code)->find();

            if (empty($api)) {
                echo $this->errorJson('1', '该参数对应接口配置不存在');
            }

            $apiParam = Db::table("mrs_api_params")->where('api_id', '=', $api['api_id'])->select();

            $data = array();
            $data = $api;
            $data['api_params'] = $apiParam;

            echo $this->successJson($data);
            exit;

        }
    }

    /**
     * 物品图片识别
     */
    public function picrecover(Request $request)
    {
        if ($request->isPost()) {
            $city = $request->post('city');
            $img = $request->post('img');

            if (empty($img)) {
                echo $this->errorJson('1', '缺少关键参数img');
                exit;
            }

            $api = Db::table("mrs_api")->where('api_code', '=', 'aliuyun_pic_cate')->find();
            if (empty($api)) {
                echo $this->errorJson('1', '该参数对应接口配置不存在');
                exit;
            }
            $apiParams = Db::table("mrs_api_params")->where('api_id', '=', $api['api_id'])->select();
            $apiParam = [];
            foreach ($apiParams as $k => $v) {
                $apiParam[$v['param_code']] = $v['param_value'];
            }

            $requestUrl = $api['api_address'];
            $method = "POST";
            $appcode = $apiParam['AppCode'];
            $headers = array();
            array_push($headers, "Authorization:APPCODE " . $appcode);
            //根据API的要求，定义相对应的Content-Type
            array_push($headers, "Content-Type" . ":" . "application/x-www-form-urlencoded; charset=UTF-8");
            $bodys = "img=" . urlencode($img);
            if (!empty($city)) {
                $querys = "city=" . urlencode($city);
                $url = $requestUrl . "?" . $querys;
            } else {
                $url = $requestUrl;
            }

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, true);
            if (strpos($requestUrl, "https://") !== false) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
            $response = curl_exec($curl);
            if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == '200') {
                $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
                $header = substr($response, 0, $headerSize);
                $body = substr($response, $headerSize);
            }
            $result = json_decode($body, true);
            $data['result'] = $result['data'][0];
            echo $this->successJson($data);
            exit;
        }
    }

    /**
     * 物品文字识别
     */
    public function wordrecover(Request $request)
    {
        if ($request->isPost()) {
            $city = $request->post('city');
            $file = $request->file('file');
            $uploadResult = $this->uploadfile($file);
            if ($uploadResult['errcode'] == '1') {
                echo $this->errorJson('1', $uploadResult['errmsg']);
                exit;
            }
            $uploadPath = $uploadResult['filePath'];
            //原文件地址，mp3
            $sourceFile = PUBLIC_PATH . $uploadPath;
            //目标文件地址，pcm
            $targetFile = PUBLIC_PATH . substr($uploadPath, 0, strripos($uploadPath, '.')) . '.pcm';

            //mp3文件转pcm语音文件
            exec("ffmpeg -y -i $sourceFile -acodec pcm_s16le -f s16le -ac 1 -ar 16000 $targetFile");

            $response = $this->voice2test($targetFile);
            //删除语音文件
            unlink($sourceFile);
            unlink($targetFile);
            if ($response['err_no'] == 0) {
                $word = filter_mark($response['result'][0]);
            } else {
                echo $this->errorJson('1', $response['err_msg']);
                exit;
            }

            $api = Db::table("mrs_api")->where('api_code', '=', 'aliuyun_word_cate')->find();
            if (empty($api)) {
                echo $this->errorJson('1', '该参数对应接口配置不存在');
                exit;
            }
            $apiParams = Db::table("mrs_api_params")->where('api_id', '=', $api['api_id'])->select();
            $apiParam = [];
            foreach ($apiParams as $k => $v) {
                $apiParam[$v['param_code']] = $v['param_value'];
            }

            $requestUrl = $api['api_address'];
            $method = "GET";
            $appcode = $apiParam['AppCode'];
            $headers = array();
            array_push($headers, "Authorization:APPCODE " . $appcode);
            if (!empty($city)) {
                $querys = 'city=' . urlencode($city) . '&name=' . urlencode($word);
            } else {
                $querys = 'name=' . urlencode($word);
            }
            $url = $requestUrl . "?" . $querys;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, true);
            if (strpos($requestUrl, "https://") !== false) {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $response = curl_exec($curl);
            if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == '200') {
                $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
                $header = substr($response, 0, $headerSize);
                $body = substr($response, $headerSize);
            } else {
                echo $this->errorJson('1', '未找到垃圾分类');
                exit;
            }
            $result = json_decode($body, true);
            $data['result'] = $result['data'];
            echo $this->successJson($data);
            exit;
        }
    }

    /**
     * 保存语音文件
     * @param $file
     * @return array
     */
    public function uploadfile($file)
    {
        if (empty($file)) {
            return array('errcode' => '1', 'errmsg' => '上传文件错误，文件资源为空');
        }
        $name = $file->getInfo('name');
        $fileInfo = pathinfo($name);
        $ext = $fileInfo['extension'];
        if($ext != 'mp3' && $ext != 'MP3'){
            return array('errcode' => '1', 'errmsg' => '音频格式错误，请转换成mp3格式文件');
        }
        $date = date('Ymd');
        $folder = 'files/voice/' . $date;

        $path = '/uploads/' . $folder;
        $filename = date('YmdHis') . rand(9999, 99999) . '.mp3';
        if (!is_dir(PUBLIC_PATH . $path)) {
            mkdir(PUBLIC_PATH . $path, 0777, true);
        }
        $info = $file->move(PUBLIC_PATH . $path, $filename);

        if ($info) {
            // 成功上传后 获取上传信息
            // 输出 jpg 地址
            $filePath = $path . '/' . $info->getSaveName();
            //文件路径
            $filePath = str_replace("\\", "/", $filePath);   //替换\为/
            return array('errcode' => '0', 'filePath' => $filePath);
        } else {
            // 上传失败获取错误信息
            $errmsg = $file->getError();

            $errmsg = empty($errmsg) ? "上传失败！" : $errmsg;
            return array('errcode' => '1', 'errmsg' => $errmsg);
        }
    }

    /**
     * 语音转文字
     * @param $pcmPath 语音文件路径
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function voice2test($pcmPath)
    {
        $api = Db::table("mrs_api")->where('api_code', '=', 'baidu_voice')->find();
        if (empty($api)) {
            echo $this->errorJson('1', '该参数对应接口配置不存在');
            exit;
        }
        $apiParams = Db::table("mrs_api_params")->where('api_id', '=', $api['api_id'])->select();
        $apiParam = [];
        foreach ($apiParams as $k => $v) {
            $apiParam[$v['param_code']] = $v['param_value'];
        }

        $APP_ID = $apiParam['AppID'];
        $API_KEY = $apiParam['APIKey'];
        $SECRET_KEY = $apiParam['SecretKey'];
        require_once ROOT_PATH . '/../extend/baidu_aip/AipSpeech.php';
        $client = new \AipSpeech($APP_ID, $API_KEY, $SECRET_KEY);
        $result = $client->asr(file_get_contents($pcmPath), 'pcm', 16000, array(
            'dev_pid' => 1537,
        ));

        return $result;
    }

}
