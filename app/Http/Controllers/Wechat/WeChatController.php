<?php

namespace App\Http\Controllers\Wechat;


use App\Events\UnitEvent;
use App\Http\Controllers\Controller;
use App\Utils\EasyRedis;
use App\Utils\RedisKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use EasyWeChat\Kernel\Messages\Voice;
use Illuminate\Support\Facades\Request;

class WeChatController extends Controller
{

    const BOT_SESSION_KEY = 'bot_session_key';
    const BOT_SET_KEY = 'bot_set_key';
    protected $bot_set;
    const UPLOAD_PATH = "/srv/laravel/blog/public/storage/";
    protected $answer;

    /**
     * 处理微信的请求消息
     *
     * @return string
     */
    public function serve()
    {
        $key = RedisKey::UNIT_BOT_MODE_SET;
        $this->bot_set = EasyRedis::get($key);
        Log::info('request arrived.'); # 注意：Log 为 Laravel 组件，所以它记的日志去 Laravel 日志看，而不是 EasyWeChat 日志
        $app = app('wechat.official_account');
        $app->server->push(function($message){
            switch ($message['MsgType'])
            {
                case "text":
                    if($message['Content'] == "设置语音回复"){
                        Redis::set(self::BOT_SET_KEY, 1);
                        return "设置语音回复成功";
                    }
                    if($message['Content'] == "设置文字回复"){
                        Redis::set(self::BOT_SET_KEY, 0);
                        return "设置文字回复成功";
                    }
                    $data = array(
                        "user_id" => $message['FromUserName'],
                        "message" => $message['Content'],
                        "bot_id" => 5886
                    );
                    $this->answer =$this->dispatchUnitEvent($data, UnitEvent::SEND);
                    break;
                case "voice":
                    $data = array(
                        "user_id" => $message['FromUserName'],
                        "message" => $message['Recognition']
                    );
                    $this->answer =$this->dispatchUnitEvent($data, UnitEvent::SEND);
                    break;
                default :
                    $this->answer = "欢迎关注 欢聚AI ———— 你的人工智能助手";
                    break;
            }
            return $this->answer;
        });
        return $app->server->serve();
    }

    protected function dispatchUnitEvent($data, $action)
    {
        new UnitEvent($data, $action);
    }


}
