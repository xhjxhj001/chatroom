<?php

namespace App\Http\Controllers\Wechat;


use App\Events\UnitEvent;
use App\Http\Controllers\Controller;
use App\Listeners\ThirdPartAPI;
use App\Listeners\UnitListener;
use App\Utils\RedisKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class WeChatController extends Controller
{
    protected $answer;

    protected $help = "欢迎关注 欢聚AI ———— 你的人工智能助手 \n" .
    "我现在还在不断学习技能中，会不定期更新一些实用的小技能。 \n" .
    "所有的技能均支持语音输入和语音回复（如果需要语音回复，可以试着说：设置语音回复，如果需要恢复文字恢复模式，输入：设置文字回复）\n" .
    "现在你可以使用以下技能：\n" .
    "技能1：全国天气查询：支持未来五天的天气情况查询；试着说：今天北京天气，或者你能想到的询问天气的方式 \n" .
    "技能2：星座查询（娱乐为主，切勿轻信）：可查询12星座当天，明天，本周，月，年的运势；试着说：巨蟹座今天运势 \n" .
    "技能3：闲聊（默认）：不用我说啦，想说啥直接和我聊天就好，虽然回答的有点智障，毕竟我是机器人啊 \n" .
    "技能4：怼人模式：慎用！，如果和怼人模式的我聊天说了不好听的，概不承认 XD；开启方式：输入：开启怼人模式，关闭方式：关闭怼人模式 \n" .
    "技能5：AI春联创作模式：输入一个主题词，我便会帮你作出一个文采飞扬的对联，还有横批，春节拜年不重样；试着说：我要作春联体验吧，如需退出，则输入“退出”即可 \n" .
    "查看此帮助信息：在任何时候输入：“帮助”即可";

    /**
     * 处理微信的请求消息
     * @return mixed
     * @throws \Exception
     */
    public function serve()
    {
        try {
            Log::info('request arrived.'); # 注意：Log 为 Laravel 组件，所以它记的日志去 Laravel 日志看，而不是 EasyWeChat 日志
            $app = app('wechat.official_account');
            $app->server->push(function ($message) {
                switch ($message['MsgType']) {
                    case "text":
                        $data = array(
                            "user_id" => $message['FromUserName'],
                            "message" => $message['Content'],
                        );
                        Log::info('request data:' . json_encode($data));
                        $this->answer = $this->dispatchUnitEvent($data, UnitEvent::SEND);
                        break;
                    case "voice":
                        $data = array(
                            "user_id" => $message['FromUserName'],
                            "message" => $message['Recognition'],
                        );
                        Log::info('request data:' . json_encode($data));
                        $this->answer = $this->dispatchUnitEvent($data, UnitEvent::SEND);
                        break;
                    default :
                        $this->answer = $this->help;
                        break;
                }
                return $this->answer;
            });
            return $app->server->serve();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

    }

    /**
     * 抛出 UNIT 事件
     * @param $data
     * @param $action
     * @return bool|string
     */
    protected function dispatchUnitEvent($data, $action)
    {
        // 检查 设置模式
        $set = $this->checkSet($data['user_id'], $data['message']);
        if ($set) {
            return $set;
        }
        // 新建事件
        $unitEvent = new UnitEvent($data, $action);
        event($unitEvent);
        return $unitEvent->result;
    }

    /**
     * 设置语音模式
     * @param $openId
     * @param $message
     * @return bool|string
     */
    protected function checkSet($openId, $message)
    {
        $key = RedisKey::UNIT_BOT_MODE_SET . $openId;
        $key_voice_set = RedisKey::UNIT_BOT_VOICE_SET . $openId;
        $key_chat_mode = RedisKey::UNIT_BOT_CHAT_SET . $openId;
        $message = trim($message, "。");
        if($message == "帮助" || $message == "查看帮助"){
            return $this->help;
        }
        if (Redis::get(RedisKey::START_COUPLETS_MODE . $openId) == 1){
            if($message == "退出"){
                Redis::del(RedisKey::START_COUPLETS_MODE . $openId);
                return "已退出春联创作模式";
            }
            $listener = new UnitListener();
            $res = $listener->getCouplets($message);
            return $res;
        }
        if ($message == "开灯") {
            Redis::set('tmp', 'light_up');
            return "好的，灯已打开";
        }
        if ($message == "关灯") {
            Redis::set('tmp', 'light_down');
            return "好的，灯已关闭";
        }
        if ($message == "我想看电影了") {
            $listener = new ThirdPartAPI();
            $url = $listener->buyMovieTickets(72);
            return $url;
        }
        if ($message == "开启怼人模式") {
            Redis::set($key_chat_mode, 1);
            return "开启怼人模式成功";
        }
        if ($message == "关闭怼人模式" || $message == "退出怼人模式") {
            Redis::set($key_chat_mode, 0);
            return "关闭怼人模式成功";
        }
        if ($message == "设置语音回复") {
            Redis::set($key, 1);
            return "设置语音回复成功";
        }
        if ($message == "设置文字回复") {
            Redis::set($key, 0);
            return "设置文字回复成功";
        }
        if ($message == "设置女生回复") {
            Redis::set($key_voice_set, UnitEvent::VOICE_WOMAN);
            return "设置女生回复成功";
        }
        if ($message == "设置男生回复") {
            Redis::set($key_voice_set, UnitEvent::VOICE_MAN);
            return "设置男生回复成功";
        }
        return false;
    }

}
