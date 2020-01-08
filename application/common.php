<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

/**
 * 记录日志
 * @param unknown_type $log_content 日志内容
 * @param unknown_type $log_file SEED_TEMP_ROOT+文件名
 */
function recordLog($log_content= 'test',$log_file='log.txt')
{
    $filename = '../public/log/'.$log_file;
    $content = "[". date('Y-m-d H:i:s') ."]".$log_content;
    if(file_exists($filename)){
        $last_content = file_get_contents($filename);
        $content = $last_content."\r\n".$content ;
    }
    file_put_contents($filename, $content);
}