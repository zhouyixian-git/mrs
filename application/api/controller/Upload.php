<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/12 0012
 * Time: 9:57
 */

namespace app\api\controller;


use think\Db;
use think\Request;

class Upload extends Base
{

    /**
     * 上传图片
     */
    public function uploadimg()
    {
        $imageCate = $this->request->param('imageCate');
        $file = $this->request->file('file');//file是传文件的名称，这是webloader插件固定写入的。因为webloader插件会写入一个隐藏input，这里与TP5的写法有点区别
        if(empty($file)){
            echo errorJson('1', '上传文件错误，文件资源为空');
            exit;
        }
        $date = date('Ymd');
        if (empty($imageCate)) {
            $folder = 'images/other/' . $date;
        } else {
            $folder = 'images/' . $imageCate . '/' . $date;
        }

        $path = '/uploads/' . $folder;
        $filename = date('YmdHis') . rand(9999, 99999) . '.png';
        if (!is_dir(PUBLIC_PATH . $path)) {
            mkdir(PUBLIC_PATH . $path, 0777, true);
        }
        $info = $file->move(PUBLIC_PATH . $path, $filename);

        if ($info) {
            // 成功上传后 获取上传信息
            // 输出 jpg 地址
            $filePath = $path . '/' . $info->getSaveName();
            $filePath = str_replace("\\", "/", $filePath);   //替换\为/

            $data =array();
            $data['filePath'] = $filePath;

            echo successJson($data);
            exit;
        } else {
            // 上传失败获取错误信息
            $errmsg = $file->getError();

            $errmsg = empty($errmsg)?"上传失败！":$errmsg;
            echo errorJson('1', $errmsg);
            exit;
        }

    }

}