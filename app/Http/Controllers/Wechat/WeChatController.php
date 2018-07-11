<?php

namespace App\Http\Controllers\Wechat;


use App\Events\UnitEvent;
use App\Http\Controllers\Controller;
use App\Utils\RedisKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class WeChatController extends Controller
{
    protected $answer;

    const BOT_BOY = 5886;
    const BOT_GIRL = 6599;
    const BOT_COMMON = 5766;

    /**
     * 处理微信的请求消息
     * @return mixed
     * @throws \Exception
     */
    public function serve()
    {
        try{
            Log::info('request arrived.'); # 注意：Log 为 Laravel 组件，所以它记的日志去 Laravel 日志看，而不是 EasyWeChat 日志
            $app = app('wechat.official_account');
            $app->server->push(function($message){
                $bot_id = $this->checkUser($message['FromUserName']);
                switch ($message['MsgType'])
                {
                    case "text":
                        if($message['Content'] == "设置语音回复"){
                            Redis::set(RedisKey::UNIT_BOT_MODE_SET, 1);
                            return "设置语音回复成功";
                        }
                        if($message['Content'] == "设置文字回复"){
                            Redis::set(RedisKey::UNIT_BOT_MODE_SET, 0);
                            return "设置文字回复成功";
                        }
                        $data = array(
                            "user_id" => $message['FromUserName'],
                            "message" => $message['Content'],
                            "bot_id" => $bot_id
                        );
                        Log::info('request data:' . json_encode($data));
                        $this->answer =$this->dispatchUnitEvent($data, UnitEvent::SEND);
                        break;
                    case "voice":
                        $data = array(
                            "user_id" => $message['FromUserName'],
                            "message" => $message['Recognition'],
                            "bot_id" => $bot_id
                        );
                        Log::info('request data:' . json_encode($data));
                        $this->answer =$this->dispatchUnitEvent($data, UnitEvent::SEND);
                        break;
                    default :
                        $this->answer = "欢迎关注 欢聚AI ———— 你的人工智能助手";
                        break;
                }
                return $this->answer;
            });
            return $app->server->serve();
        }catch (\Exception $e){
            throw new \Exception($e->getMessage(), $e->getCode());
        }

    }

    protected function dispatchUnitEvent($data, $action)
    {
        $unitEvent = new UnitEvent($data, $action);
        event($unitEvent);
        return $unitEvent->result;
    }

    /**
     * 检查用户来源
     * @param $openId
     * @return int
     */
    protected function checkUser($openId)
    {
        switch ($openId)
        {
            case "oLIAK0hNsAVzyxh3pmWLSNfNibwc":
                $bot_id = self::BOT_BOY;
                break;
            case "oLIAK0gFuUw_yGsUdBxPxiXmJKeo":
                $bot_id = self::BOT_GIRL;
                break;
            default:
                $bot_id = self::BOT_COMMON;
                break;
        }
        return $bot_id;
    }

}
