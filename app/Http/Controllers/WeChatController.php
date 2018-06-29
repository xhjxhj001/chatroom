<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

class WeChatController extends Controller
{

    /**
     * 处理微信的请求消息
     *
     * @return string
     */
    public function serve()
    {
        Log::info('request arrived.'); # 注意：Log 为 Laravel 组件，所以它记的日志去 Laravel 日志看，而不是 EasyWeChat 日志

        $app = app('wechat.official_account');
        $app->server->push(function($message){
            if($message['MsgType'] == "text"){
                return $this->sendToUnit($message['FromUserName'], $message['Content']);
            }else{
                return "欢迎关注 overtrue！";
            }
        });
        return $app->server->serve();
    }

    public function sendToUnit($id, $message)
    {
        $access_token = getenv("UNIT_TOKEN");
        $url = "https://aip.baidubce.com/rpc/2.0/unit/bot/chat?access_token=" . $access_token;
        $data = array(
            "bot_id" => 5,
            "version" => "2.0",
            "bot_session" => "",
            "log_id" => "77585212",
            "request" => array(
                "bernard_level" => 1,
                "client_session" => array(
                    "client_results" => "",
                    "candidate_options" => [],
                ),
                "query" => $message,
                "query_info" => array(
                    "asr_candidates" => [],
                    "source" => "KEYBOARD",
                    "type" => "TEXT"
                ),
                "updates" => "",
                "user_id" => $id,
            ),
        );
        $body = json_encode($data);
        $res = $this->request_post($url, $body);
        $action_list = $res['result']['response']['action_list'];
        $answer_index = mt_rand(0, count($action_list) - 1);
        $answer = $action_list[$answer_index]['say'];
        return $answer;
    }

    /**
     * 模拟post进行url请求
     * @param string $url
     * @param string $param
     * @return array|bool $data
     **/
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
        $data = json_decode($data, true);
        curl_close($ch);
        return $data;
    }

}