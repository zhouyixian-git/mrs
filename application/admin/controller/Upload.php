<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/16 0016
 * Time: 21:26
 */

namespace app\admin\controller;


use think\Exception;
use think\File;

class Upload extends Base
{

    public function index()
    {
    }

    /**
     * 上传图片
     */
    public function upload()
    {
        $imageCate = $this->request->param('imageCate');
        $file = $this->request->file('file');//file是传文件的名称，这是webloader插件固定写入的。因为webloader插件会写入一个隐藏input，这里与TP5的写法有点区别
        $date = date('Ymd');
        $name = $file->getInfo('name');
        $fileInfo = pathinfo($name);
        $ext = $fileInfo['extension'];
        if (in_array($ext, ['gif', 'jpg', 'jpeg', 'bmp', 'png'])) {
            if (empty($imageCate)) {
                $folder = 'images/avator/' . $date;
            } else {
                $folder = 'images/' . $imageCate . '/' . $date;
            }
        }else{
            if (empty($imageCate)) {
                $folder = 'files/avator/' . $date;
            } else {
                $folder = 'files/' . $imageCate . '/' . $date;
            }
        }
        $path = '/uploads/' . $folder;
        $filename = date('YmdHis') . rand(9999, 99999) . '.' . $ext;

        if (!is_dir(PUBLIC_PATH . $path)) {
            mkdir(PUBLIC_PATH . $path, 0777, true);
        }
        $info = $file->move(PUBLIC_PATH . $path, $filename);

        if ($info) {
            // 成功上传后 获取上传信息
            // 输出 jpg 地址
            $filePath = $path . '/' . $info->getSaveName();
            $filePath = str_replace("\\", "/", $filePath);   //替换\为/
            echo json_encode(array('success' => true, 'filePath' => $filePath));
            return;
        } else {
            // 上传失败获取错误信息
            echo $file->getError();
            return;
        }

    }

}
