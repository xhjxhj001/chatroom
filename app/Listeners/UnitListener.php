<?php

namespace App\Listeners;

use App\Events\UnitEvent;
use App\Utils\RedisKey;
use EasyWeChat\Kernel\Messages\Voice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UnitListener extends BaseListener
{
    /**
     * @param UnitEvent $event
     */
    public function handle(UnitEvent $event)
    {
        switch ($event->action) {
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
        $res = $this->sendToUnitBot($event->bot_id, $event->message, $event->user_id, $event->bot_session);
        $action_list = $res['result']['response']['action_list'];
        $answer_index = mt_rand(0, count($action_list) - 1);
        $type = $action_list[$answer_index]['type']; // 回复类型
        $reply = $action_list[$answer_index]['custom_reply']; // 自定义回复函数
        if (!empty($reply)) {
            $reply = json_decode($reply, true);
            $slots = $res['result']['response']['schema']['slots'];
            $result = $this->checkFunc($reply['func'], $slots, $event);
        } else if ($type == "guide" || $type == "failure") {
            $result = $this->sendToChatBot($event);
        } else {
            $result = $action_list[$answer_index]['say'];
        }
        $event->setBotSession($event->user_id, $res['result']['bot_session']);
        $event->setResult($result);

    }

    /**
     * 发送给闲聊机器人（无功能）
     * @param UnitEvent $event
     * @return mixed
     */
    public function sendToChatBot(UnitEvent $event)
    {
        $res = $this->sendToUnitBot(UnitEvent::BOT_CHAT, $event->message, $event->user_id, "");
        $action_list = $res['result']['response']['action_list'];
        $answer_index = mt_rand(0, count($action_list) - 1);
        $answer = $action_list[$answer_index]['say'];
        return $answer;
    }

    /**
     * 发送给UNIT机器人
     * @param int $bot_id 机器人id
     * @param string $query 发送内容
     * @param string $user_id 用户id
     * @param string $bot_session bot session
     * @return array|bool
     */
    public function sendToUnitBot($bot_id, $query, $user_id, $bot_session)
    {
        $access_token = Redis::get(RedisKey::BAIDU_UNIT_TOKEN);
        $url = "https://aip.baidubce.com/rpc/2.0/unit/bot/chat?access_token=" . $access_token;
        $data = array(
            "bot_id" => $bot_id,
            "version" => "2.0",
            "bot_session" => $bot_session,
            "log_id" => time(),
            "request" => array(
                "bernard_level" => 1,
                "client_session" => json_encode(array(
                    "client_results" => "",
                    "candidate_options" => [],
                )),
                "query" => $query,
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
        return $res;
    }

    public function getCouplets($keywords)
    {
        $access_token = Redis::get(RedisKey::BAIDU_UNIT_TOKEN);
        $url = "https://aip.baidubce.com/rpc/2.0/nlp/v1/couplets?access_token=" . $access_token;
        $index = mt_rand(0, 10);
        $data = array(
            "text" => $keywords,
            "index" => $index,
        );
        $body = json_encode($data);
        $res = $this->request_post($url, $body);
        if (isset($res['couplets'])) {
            $message = "作出来啦，请看：\n" .
                "上联：" . $res['couplets']['first'] . "\n" .
                "下联：" . $res['couplets']['second'] . "\n" .
                "横批：" . $res['couplets']['center'];
            $result =  $message;
        } else {
            $result = "哎呀，还真作不出来";
        }
        return $result;
    }

    /**
     * 查询天气
     * @param string $city 城市
     * @param string $date_input 用户输入日期
     * @param string $date_nor unit 识别日期
     * @param int $response_mode 回复方式
     * @return mixed|string
     */
    protected function BaiduWeather($city, $date_input, $date_nor)
    {
        $oneday = 60 * 60 * 24;
        $today = strtotime(date("Y-m-d", time()));
        $time = strtotime($date_nor);
        $num = ($time - $today) / $oneday;
        if ($num < 0 || $num > 3) {
            return "对不起，无法查询{$city}{$date_input}的天气情况";
        }
        $ak = getenv("BAIDU_AK");
        $url = "http://api.map.baidu.com/telematics/v3/weather?location=$city&output=json&ak=$ak";
        $res = $this->request_get($url);
        if ($res['error'] == 0) {
            $forecast = $res["results"][0]["weather_data"][$num];
            $current_tem = $res["results"][0]["weather_data"][0]["date"];
            $current_tem = explode("：", $current_tem);
            $current_tem = $current_tem[1];
            $current_tem = substr($current_tem, 0, -1);
            $response = $city . "当前温度：" . $current_tem . "\n" .
                $date_input . $forecast['weather'] . "\n" .
                "温度：" . $forecast['temperature'] . "\n" .
                "风力：" . $forecast['wind'];
        } else {
            $response = "对不起，找不到{$city}的天气情况";
        }
        return $response;
    }

    /**
     * 文字转换成微信语音消息
     * @param $text
     * @param $voice
     * @return Voice
     */
    public function trans2voice($text, $voice, $user_id)
    {
        $mediaId = $this->text2audio($text, $voice, $user_id);
        return new Voice($mediaId);
    }

    /**
     * baidu api 将文字转换语音并上传
     * @param $text
     * @param $voice
     * @return mixed
     */
    protected function text2audio($text, $voice, $user_id)
    {
        $tok = Redis::get(RedisKey::BAIDU_VOICE_TOKEN);
        $url = "https://tsn.baidu.com/text2audio?tex=$text&lan=zh&cuid=***&ctp=1&tok=$tok&per=" . $voice;
        $this->request_get($url, true, storage_path("app/public/{$user_id}_audio.mp3"));
        $mediaId = $this->uploadMedia($user_id);
        return $mediaId;
    }

    /**
     * 上次音频文件，获取mediaId
     * @return mixed
     */
    protected function uploadMedia($user_id)
    {
        $app = app('wechat.official_account');
        $path = storage_path("app/public/{$user_id}_audio.mp3");
        $res = $app->media->uploadVoice($path);
        return $res["media_id"];
    }

    /**
     * 检查自定义功能
     * @param $func
     * @param $slots
     * @param UnitEvent $event
     * @return mixed|string
     */
    protected function checkFunc($func, $slots, UnitEvent $event)
    {
        $result = "查询失败";
        switch ($func) {
            case "unit_search_weather":
                foreach ($slots as $slot) {
                    if ($slot['name'] == "user_location") {
                        $city = $slot['original_word'];
                    }
                    if ($slot['name'] == "user_time") {
                        $date_input = $slot['original_word'];
                        $date_nor = $slot['normalized_word'];
                    }
                }
                $result = $this->BaiduWeather($city, $date_input, $date_nor);
                break;
            case "unit_search_constellation":
                foreach ($slots as $slot) {
                    if ($slot['name'] == "user_constellation") {
                        $name = $slot['normalized_word'];
                    }
                    if ($slot['name'] == "user_time") {
                        $date = $slot['original_word'];
                        $date = $this->checkDate($date);
                    }
                }
                $api = new ThirdPartAPI();
                $result = $api->checkConstellation($name, $date);
                break;
            case "unit_power_control":
                foreach ($slots as $slot) {
                    if ($slot['name'] == "user_power_name") {
                        $name = $slot['normalized_word'];
                    }
                    if ($slot['name'] == "user_power_action") {
                        $action = $slot['original_word'];
                    }
                }
                $api = new ThirdPartAPI();
                //Log::info("[power_control]" . $action.$name);
                $result = $api->powerControl($action, $name);
                break;
            case "unit_search_couplets":
                Redis::set(RedisKey::START_COUPLETS_MODE . $event->user_id, 1);
                $result = "请输入关键词，例如：金猪贺岁，大年，拜年，好运，生意，财运，手机等等关键词，五个字以内即可，可以大胆尝试新鲜词汇，看看能不能难倒我，^_^";
                break;
            default:
                break;
        }
        return $result;

    }

    private function checkDate($date)
    {
        if ($date == "今天") {
            return "today";
        }
        if ($date == "明天") {
            return "tomorrow";
        }
        if (strpos($date, "周") !== false) {
            return "week";
        }
        if (strpos($date, "月") !== false) {
            return "month";
        }
        if (strpos($date, "年") !== false) {
            return "year";
        }
    }

}
