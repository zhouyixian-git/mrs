<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/7 0007
 * Time: 15:00
 */

namespace app\api\Model;


use think\Db;
use think\Model;
use think\Request;

class User extends Model
{

    //设置数据表名
    protected $table = 'mrs_user';


    function getFaceApiParam()
    {
        $post_data = array();
        $FaceApi = Db::table('mrs_api')->where(array('api_code' => 'BaiduFaceApi', 'status' => '1'))->find();

        if (empty($FaceApi)) {
            return errorJson('1', 'Not found the sms api!');
        }

        $group_id = 'jiaYuan';
        $params = Db::table('mrs_api_params')->where(array('api_id' => $FaceApi['api_id']))->select();
        if (is_array($params) && count($params)) {
            foreach ($params as $k => $param) {
                if ($param['param_code'] == 'api_key') {
                    $post_data['client_id'] = $param['param_value'];
                } else if ($param['param_code'] == 'secret_key') {
                    $post_data['client_secret'] = $param['param_value'];
                } else if ($param['param_code'] == 'group_id') {
                    $group_id = $param['param_value'];
                }
            }
        }

        $url = 'https://aip.baidubce.com/oauth/2.0/token';
        $post_data['grant_type'] = 'client_credentials';
        $o = "";
        foreach ( $post_data as $k => $v )
        {
            $o.= "$k=" . urlencode( $v ). "&" ;
        }
        $post_data = substr($o,0,-1);

        $res = doPostHttp($url, $post_data);
        $result = json_decode($res, true);

        $result['group_id'] = $group_id;
        return $result;
        exit;
    }

    public function faceset($user_id){
        $user = $this->where('user_id','=',$user_id)->find();

        if(empty($user)){
            return false;
        }

        $res = $this->getFaceApiParam();
        $token = $res['access_token'];
        $url = 'https://aip.baidubce.com/rest/2.0/face/v3/faceset/user/add?access_token=' . $token;

        $data = array();
        $image = imgToBase64(APP_ROOT_PATH . $user['face_img']);
        $data["image"]= $image;
        $data["image_type"]="BASE64";
        $data["group_id"]= $res['group_id'];
        $data["user_id"]=$user_id;
        $data["user_info"]=$user_id;
        $data["quality_control"]="LOW";
        $data["liveness_control"]="NORMAL";

        $posts = json_encode($data);
        $res = httpPost($url, $posts);

        return $res;
    }

    public function searchFace($base64_image){
        $res = $this->getFaceApiParam();
        $token = $res['access_token'];

        $url = 'https://aip.baidubce.com/rest/2.0/face/v3/search?access_token=' . $token;

        if(empty($base64_image)){
            return false;
        }

        $data = array();
//        $image = imgToBase64(APP_ROOT_PATH . $image_path);
        $data["image"]=$base64_image;
        $data["image_type"]="BASE64";
        $data["group_id_list"]=$res['group_id'];
        $data["quality_control"]="LOW";
        $data["liveness_control"]="NORMAL";

        $posts = json_encode($data);

        $res = httpPost($url, $posts);

        return $res;
    }

}