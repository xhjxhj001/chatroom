<?php

namespace App\Events;

use App\Utils\EasyRedis;
use App\Utils\RedisKey;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UnitEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    const SEND = 1;

    public $action; // 操作
    public $bot_id; // 机器人id
    public $user_id; // 用户id
    public $response_mode; // 回复方式 1: 语音回复;  0: 文字回复
    public $voice_mode; // 语音模式
    public $bot_session; // bot session
    public $message; // 发送消息

    protected $response; // 回复消息

    /**
     * 组装必须数据
     * UnitEvent constructor.
     * @param $data
     * @param int $action
     */
    public function __construct($data, $action)
    {
        $this->action = $action;
        //获取 bot session
        $bot_session = EasyRedis::get(RedisKey::UNIT_BOT_SESSION);
        $this->bot_session = empty($bot_session) ? "" : $bot_session;
        //获取 bot 回复模式
        $response_mode = EasyRedis::get(RedisKey::UNIT_BOT_MODE_SET);
        $this->response_mode = empty($response_mode) ? 0 : $response_mode ;
        if($response_mode != 0){
            $voice_mode = EasyRedis::get(RedisKey::UNIT_BOT_VOICE_SET);
            $this->voice_mode = empty($voice_mode) ? 1 : $voice_mode;
        }
        //获取 bot 语音模式
        $this->bot_id = $data['bot_id'];
        $this->user_id = $data['user_id'];
        $this->message = $data['message'];
    }

    public function setResult($response)
    {
        $this->response = $response;
    }

    public function setBotSession($bot_session)
    {
        $key = RedisKey::UNIT_BOT_SESSION;
        EasyRedis::set($key, $bot_session, 60);
    }

    public function __destruct()
    {
        return $this->response;
    }

}
