<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use EasyWeChat\Kernel\Messages\Voice;

class WeChatController extends Controller
{

    const BOT_SESSION_KEY = 'bot_session_key';
    const RESPONSE_WAY = 1; // 1:语音; 2:文字

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
                return $this->sendToBot($message['FromUserName'], $message['Content']);
            }else if($message['MsgType'] == "voice") {
                return $this->sendToBot($message['FromUserName'], $message['Recognition']);
            }else{
                return "欢迎关注 欢聚AI ———— 你的人工智能助手";
            }
        });
        return $app->server->serve();
    }

    /**
     * 发送给 AI 机器人 5886
     * @param $user_id
     * @param $message
     * @return mixed|string|
     */
    public function sendToBot($user_id, $message)
    {
        $bot_session = Redis::get(self::BOT_SESSION_KEY);
        $access_token = getenv("UNIT_TOKEN");
        $url = "https://aip.baidubce.com/rpc/2.0/unit/bot/chat?access_token=" . $access_token;
        $data = array(
            "bot_id" => 5886,
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
                        $city = $slot['original_word'];
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
        Redis::expire(self::BOT_SESSION_KEY, 60);
        if(self::RESPONSE_WAY == 1){
            return $this->trans2voice($answer);
        }else{
            return $answer;
        }
    }

    /**
     * 发送给闲聊机器人（无功能）
     * @param $user_id
     * @param $message
     * @return mixed
     */
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
        $res = $this->request_get($url);
        if(isset($res['status'])){
            $forecast = $res["data"]["forecast"][$date];
            $response = $city . "今天" . $forecast['type'] . "\n" .
                "当前温度：" . $res['data']['wendu'] . "\n" .
                "最高气温：" . $forecast['high'] . "\n" .
                "最低气温：" . $forecast['low'] . "\n" .
                "空气质量：" . $forecast['aqi'] . "\n" .
                "风向：" . $forecast['fx'] . "\n" .
                "风力：" . $forecast['fl'] . "\n" .
                "欢聚提醒你：" . $forecast['notice'];
            if(self::RESPONSE_WAY == 1){
                $response = $city . "今天" . $forecast['type'] .
                    "当前温度" . $res['data']['wendu'] .
                    "最高气温" . $forecast['high'] .
                    "最低气温" . $forecast['low'] .
                    "空气质量" . $forecast['aqi'] .
                    "风向" . $forecast['fx'] .
                    "风力" . $forecast['fl']  .
                    "欢聚提醒你" . $forecast['notice'];
                $response = str_replace(' ', '', $response);
            }
        }else{
            $response = "对不起，找不到 " .$city . "的天气情况";
        }
        Log::info($response);
        return $response;
    }

    /**
     * 文字转换成微信语音消息
     * @param $text
     * @return Voice
     */
    protected function trans2voice($text)
    {
        $mediaId = $this->text2audio($text);
        return new Voice($mediaId);
    }

    /**
     * baidu api 将文字转换语音并上传
     * @param $text
     * @return mixed
     */
    protected function text2audio($text)
    {
        $tok = getenv("VOICE_TOKEN");
        $url = "https://tsn.baidu.com/text2audio?tex=$text&lan=zh&cuid=***&ctp=1&tok=$tok&per=3";
        $this->request_get($url, false);
        $mediaId = $this->uploadMedia();
        return $mediaId;
    }

    /**
     * 上次音频文件，获取mediaId
     * @return mixed
     */
    protected function uploadMedia()
    {
        $app = app('wechat.official_account');
        $path = "/srv/laravel/blog/public/audio.mp3";
        $res = $app->media->uploadVoice($path);
        return $res["media_id"];
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
        Log::info($url);
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

    /**
     * 模拟get进行url请求
     * @param string $url
     * @return array|bool $data
     **/
    protected function request_get($url = '', $json = true)
    {
        if (empty($url)){
            return false;
        }
        $postUrl = $url;
        Log::info($url);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        $data = curl_exec($ch);//运行curl
        if($json){
            $data = json_decode($data, true);
        }else{
            $res = file_put_contents('/srv/laravel/blog/public/audio.mp3', $data);
            Log::info($res);
        }
        curl_close($ch);
        return $data;
    }


}
