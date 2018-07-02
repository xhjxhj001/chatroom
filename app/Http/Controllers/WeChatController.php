<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class WeChatController extends Controller
{

    const BOT_SESSION_KEY = 'bot_session_key';

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
                $bot_id = 5886;
                return $this->sendToBot($bot_id, $message['FromUserName'], $message['Content']);
            }else{
                return "欢迎关注 overtrue！";
            }
        });
        return $app->server->serve();
    }

    public function sendToBot($bot_id, $user_id, $message)
    {
        $bot_session = Redis::get(self::BOT_SESSION_KEY);
        $access_token = getenv("UNIT_TOKEN");
        $url = "https://aip.baidubce.com/rpc/2.0/unit/bot/chat?access_token=" . $access_token;
        $data = array(
            "bot_id" => $bot_id,
            "version" => "2.0",
            "bot_session" => empty($bot_session) ? "" : $bot_session,
            "log_id" => "77585212",
            "request" => array(
                "bernard_level" => 1,
                "client_session" => json_encode(array(
                    "client_results" => "",
                    "candidate_options" => [],
                )),
                "query" => $message,
                "query_info" => array(
                    "asr_candidates" => [],
                    "source" => "KEYBOARD",
                    "type" => "TEXT"
                ),
                "updates" => "",
                "user_id" => $user_id,
            ),
        );
        $body = json_encode($data);
        $res = $this->request_post($url, $body);
        $action_list = $res['result']['response']['action_list'];
        $answer_index = mt_rand(0, count($action_list) - 1);
        $type = $action_list[$answer_index]['type'];
        $reply = $action_list[$answer_index]['custom_reply'];
        if(!empty($reply)){
            $reply = json_decode($reply, true);
            if($reply['func'] == "unit_search_weather"){
                $slots = $res['result']['response']['schema']['slots'];
                foreach ($slots as $slot){
                    if($slot['name'] == "user_location"){
                        $city = explode(">", $slot['normalized_word']);
                        $city = $city[1];
                    }
                }
                $answer = $this->checkWeather($city);
            }
        }else if($type == "guide"){
            $answer = $this->sendToChatBot($user_id, $message);
        }else{
            $answer = $action_list[$answer_index]['say'];
        }
        Redis::set(self::BOT_SESSION_KEY, $res['result']['bot_session']);
        return $answer;
    }

    public function sendToChatBot($user_id, $message)
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
                "client_session" => json_encode(array(
                    "client_results" => "",
                    "candidate_options" => [],
                )),
                "query" => $message,
                "query_info" => array(
                    "asr_candidates" => [],
                    "source" => "KEYBOARD",
                    "type" => "TEXT"
                ),
                "updates" => "",
                "user_id" => $user_id,
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
     * 查询天气
     * @param $city
     * @param int $date
     * @return string
     */
    protected function checkWeather($city, $date = 0)
    {
        $url = "https://www.sojson.com/open/api/weather/json.shtml?city=" . $city;
        $res = $this->request_post($url);
        $forecast = $res["data"]["forecast"][$date];
        $response = $city . "今天" . $forecast['type'] . "\n" .
                    "最高气温：" . $forecast['high'] . "\n" .
                    "最低气温：" . $forecast['low'] . "\n" .
                    "空气质量：" . $forecast['aqi'] . "\n" .
                    "风向：" . $forecast['fx'] . "\n" .
                    "风力：" . $forecast['fl'] . "\n" .
                    "欢聚提醒你：" . $forecast['notice'];
        return $response;
    }

    /**
     * 模拟post进行url请求
     * @param string $url
     * @param string $param
     * @return array|bool $data
     **/
    protected function request_post($url = '', $param = '')
    {
        Log::info("curl start url: $url body: $param");
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
        Log::info("curl end result: $data");
        $data = json_decode($data, true);
        curl_close($ch);
        return $data;
    }

}
