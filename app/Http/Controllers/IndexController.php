<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Redis;

class IndexController extends BaseController
{
    public function index()
    {
        $data = Redis::get('tmp');
        if(!empty($data)){
            return json_encode(array("errno" => 0, "errmsg" => "success", "data" => $data));
        }else{
            $data = Redis::set('tmp', 'xlp');
            return json_encode(array("errno" => 0, "errmsg" => "success", "data" => 'insert to redis success'));
        }
    }

    public function login(Request $request)
    {
        $appid = getenv('WECHAT_APP_APPID');
        $secret = getenv('WECHAT_APP_SECRET');
        $code = $request['code'];
        $data = array(
            'appid' => $appid,
            'secret' => $secret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        );
        $url = 'https://api.weixin.qq.com/sns/jscode2session';
        $res = $this->request_post($url, $data);
        return json_encode(array("errno" => 0, "errmsg" => "success", "data" => $res));
    }

    /**
     * 模拟post进行url请求
     * @param string $url
     * @param string $param
     * @return array $data
     */
    protected function request_post($url = '', $param = '')
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
    }
}
