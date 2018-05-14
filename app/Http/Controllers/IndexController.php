<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class IndexController extends Controller
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
        $res = json_decode($res, true);
        if($data = $this->getUser($res['openid'])){
            return json_encode(array("errno" => 0, "errmsg" => "success", "data" => $data));
        }else{
            $this->insertUser($request, $res['openid']);
            $data = $this->getUser($res['openid']);
            return json_encode(array("errno" => 0, "errmsg" => "success", "data" => $data));
        }

    }

    public function signUp(Request $request)
    {
        $openid = $request['openid'];
        $user = $this->getUser($openid);
        $res = $this->insertToList($user);
        return json_encode(array("errno" => 0, "errmsg" => "success", "data" => $res));
    }

    protected function getUser($openid)
    {
        $user = DB::table('user')->where('openid', $openid)->first();
        return $user;
    }

    protected function insertToList($user)
    {
        $time = time();
        $data = array(
            'user_id' => $user['id'],
            'ctime' => $time,
            'mtime' => $time
        );
        $res = DB::table('sign_up')->insert($data);
        return $res;
    }

    protected function insertUser($request, $openid)
    {
        $time = time();
        $user = array(
            'name' => $request['name'],
            'avatar' => $request['avatar'],
            'openid' => $openid,
            'status' => 1,
            'ctime' => $time,
            'mtime' => $time
        );
        $res = DB::table('user')->insert($user);
        return $res;
    }

    /**
     * 模拟post进行url请求
     * @param string $url
     * @param string $param
     * @return string $data
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
