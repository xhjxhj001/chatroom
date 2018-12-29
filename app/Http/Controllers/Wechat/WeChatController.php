<?php

namespace App\Http\Controllers\Wechat;


use App\Events\UnitEvent;
use App\Http\Controllers\Controller;
use App\Listeners\ThirdPartAPI;
use App\Utils\RedisKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class WeChatController extends Controller
{
    protected $answer;

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
                switch ($message['MsgType'])
                {
                    case "text":
                        $data = array(
                            "user_id" => $message['FromUserName'],
                            "message" => $message['Content'],
                        );
                        Log::info('request data:' . json_encode($data));
                        $this->answer =$this->dispatchUnitEvent($data, UnitEvent::SEND);
                        break;
                    case "voice":
                        $data = array(
                            "user_id" => $message['FromUserName'],
                            "message" => $message['Recognition'],
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
        if($set){
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
	if($message == "开灯"){
	    Redis::set('tmp', 'lightup');
	    return "好的，灯已打开";
	}
	if($message == "关灯"){
            Redis::set('tmp', 'lightdown');
	    return "好的，灯已关闭";
        }
        if($message == "我想看电影了"){
            $listener = new ThirdPartAPI();
            $url = $listener->buyMovieTickets(72);
            return $url;
        }
        if($message == "开启怼人模式"){
            Redis::set($key_chat_mode, 1);
            return "开启怼人模式成功";
        }
        if($message == "关闭怼人模式"){
            Redis::set($key_chat_mode, 0);
            return "关闭怼人模式成功";
        }
        if($message == "设置语音回复"){
            Redis::set($key, 1);
            return "设置语音回复成功";
        }
        if($message == "设置文字回复"){
            Redis::set($key, 0);
            return "设置文字回复成功";
        }
        if($message == "设置女生回复"){
            Redis::set($key_voice_set, UnitEvent::VOICE_WOMAN);
            return "设置女生回复成功";
        }
        if($message == "设置男生回复"){
            Redis::set($key_voice_set, UnitEvent::VOICE_MAN);
            return "设置男生回复成功";
        }
        return false;
    }

}
