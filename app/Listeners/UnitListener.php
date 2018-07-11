<?php

namespace App\Listeners;

use App\Events\UnitEvent;
use EasyWeChat\Kernel\Messages\Voice;
use Illuminate\Support\Facades\Log;

class UnitListener extends BaseListener
{
    /**
     * @param UnitEvent $event
     */
    public function handle(UnitEvent $event)
    {
        switch ($event->action)
        {
            case UnitEvent::SEND:
                $this->onSend($event);
                break;
            default:
                break;
        }
    }

    /**
     * 发送给 AI 机器人
     * @param UnitEvent $event
     * @return Voice|string|void
     */
    public function onSend(UnitEvent $event)
    {
        $access_token = getenv("UNIT_TOKEN");
        $url = "https://aip.baidubce.com/rpc/2.0/unit/bot/chat?access_token=" . $access_token;
        $data = array(
            "bot_id" => $event->bot_id,
            "version" => "2.0",
            "bot_session" => $event->bot_session,
            "log_id" => time(),
            "request" => array(
                "bernard_level" => 1,
                "client_session" => json_encode(array(
                    "client_results" => "",
                    "candidate_options" => [],
                )),
                "query" => $event->message,
                "query_info" => array(
                    "asr_candidates" => [],
                    "source" => "KEYBOARD",
                    "type" => "TEXT"
                ),
                "updates" => "",
                "user_id" => $event->user_id,
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
                $result = $this->checkWeather($event, $city);
            }
        }else if($type == "guide"){
            $result = $this->sendToChatBot($event);
        }else{
            $result = $action_list[$answer_index]['say'];
        }
        $event->setBotSession($res['result']['bot_session']);
        // 如果开启语音回复模式，则转换成语音
        if($event->response_mode){
            $result = $this->trans2voice($result, $event->voice_mode);
        }
        $event->setResult($result);

    }

    /**
     * 发送给闲聊机器人（无功能）
     * @param UnitEvent $event
     */
    public function sendToChatBot(UnitEvent $event)
    {
        $access_token = getenv("UNIT_TOKEN");
        $url = "https://aip.baidubce.com/rpc/2.0/unit/bot/chat?access_token=" . $access_token;
        $data = array(
            "bot_id" => 5,
            "version" => "2.0",
            "bot_session" => "",
            "log_id" => time(),
            "request" => array(
                "bernard_level" => 1,
                "client_session" => json_encode(array(
                    "client_results" => "",
                    "candidate_options" => [],
                )),
                "query" => $event->message,
                "query_info" => array(
                    "asr_candidates" => [],
                    "source" => "KEYBOARD",
                    "type" => "TEXT"
                ),
                "updates" => "",
                "user_id" => $event->user_id,
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
    protected function checkWeather(UnitEvent $event, $city, $date = 0)
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
            if($event->response_mode){
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
     * @param $voice
     * @return Voice
     */
    protected function trans2voice($text, $voice)
    {
        $mediaId = $this->text2audio($text, $voice);
        return new Voice($mediaId);
    }

    /**
     * baidu api 将文字转换语音并上传
     * @param $text
     * @param $voice
     * @return mixed
     */
    protected function text2audio($text, $voice)
    {
        $tok = getenv("VOICE_TOKEN");
        $url = "https://tsn.baidu.com/text2audio?tex=$text&lan=zh&cuid=***&ctp=1&tok=$tok&per=" . $voice;
        $this->request_get($url, true, storage_path("app/public/audio.mp3"));
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
        $path = storage_path("app/public/audio.mp3");
        $res = $app->media->uploadVoice($path);
        return $res["media_id"];
    }

}
