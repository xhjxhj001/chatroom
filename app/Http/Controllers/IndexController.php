<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class IndexController extends Controller
{

    /**
     * 首页测试接口
     * @return string
     */
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

    /**
     * 登录接口
     * @param Request $request
     * @return string
     */
    public function login(Request $request)
    {
	#var_dump($request['name'],$request['code']);die;
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

    /**
     * 报名接口
     * @param Request $request
     * @return string
     */
    public function signUp(Request $request)
    {
        if(empty($request['openid'])){
            return json_encode(array("errno" => 880331, "errmsg" => "openid error", "data" => null));
        }
            $openid = $request['openid'];
        $user = $this->getUser($openid);
        $res = $this->insertToList($user);
        if($res == true){
            return json_encode(array("errno" => 0, "errmsg" => "success", "data" => $res));
        }else{
                return json_encode(array("errno" => 880332, "errmsg" => "已报名", "data" => $res));
        }
    }

    /**
     * 获取报名列表
     * @return string
     */
    public function getSignList()
    {
        $res = DB::table('sign_up')
            ->select('user.name', 'user.avatar', 'sign_up.ctime')
            ->leftjoin('user', 'sign_up.user_id', '=', 'user.id')
            ->get();
        foreach($res as &$item){
            $item->ctime = date('Y-m-d H:i:s', $item->ctime);
        }
        return json_encode(array("errno" => 0, "errmsg" => "success", "data" => $res));
    }

    /**
     * 获取用户信息
     * @param $openid
     * @return mixed
     */
    protected function getUser($openid)
    {
        $user = DB::table('user')->where('openid', $openid)->first();
	    return $user;
    }

    /**
     * 插入用户到报名列表
     * @param $user
     * @return mixed
     */
    protected function insertToList($user)
    {
	$sign = DB::table('sign_up')->where('user_id', $user->id)->first();
	if($sign){
	    return false;
	}
        $time = time();
        $data = array(
            'user_id' => $user->id,
            'ctime' => $time,
            'mtime' => $time
        );
        $res = DB::table('sign_up')->insert($data);
        return $res;
    }

    /**
     * 插入用户到用户表
     * @param $request
     * @param $openid
     * @return mixed
     */
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
