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
use Illuminate\Support\Facades\Redis;

class UnitEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    const SEND = 1;  // 发送操作
    const BOT_BOY = 5886; // 男机器人
    const BOT_GIRL = 6599; // 女机器人
    const BOT_COMMON = 5766; // 普通机器人
    const BOT_CHAT = 5; // 闲聊机器人

    const VOICE_WOMAN = 0; // 女性声音
    const VOICE_MAN = 1; // 男性声音


    public $action; // 操作
    public $bot_id; // 机器人id
    public $user_id; // 用户id
    public $response_mode; // 回复方式 1: 语音回复;  0: 文字回复
    public $voice_mode; // 语音模式
    public $bot_session; // bot session
    public $message; // 发送消息

    public $result; // 回复消息

    /**
     * 组装必须数据
     * UnitEvent constructor.
     * @param $data
     * @param int $action
     */
    public function __construct($data, $action)
    {
        $user_id = $data['user_id'];
        $this->action = $action;
        $this->checkUser($user_id);
        $this->user_id = $user_id;
        $this->message = $data['message'];
        //获取 bot session
        $bot_session = EasyRedis::get(RedisKey::UNIT_BOT_SESSION . $user_id);
        $this->bot_session = empty($bot_session) ? "" : $bot_session;
        //获取 bot 回复模式
        $response_mode = EasyRedis::get(RedisKey::UNIT_BOT_MODE_SET . $user_id);
        $this->response_mode = empty($response_mode) ? 0 : $response_mode ;
        if($response_mode != 0){
            $voice_mode = EasyRedis::get(RedisKey::UNIT_BOT_VOICE_SET . $user_id);
            $this->voice_mode = empty($voice_mode) ? self::VOICE_WOMAN : $voice_mode;
        }
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    public function setBotSession($user_id, $bot_session)
    {
        $key = RedisKey::UNIT_BOT_SESSION . $user_id;
        EasyRedis::set($key, $bot_session, 60);
    }

    public function setVoiceMode($openId, $mode)
    {
        $key = RedisKey::UNIT_BOT_MODE_SET . $openId;
        Redis::set($key, $mode);
    }

    /**
     * 检查用户来源
     * @param $user_id
     */
    protected function checkUser($user_id)
    {
        $key = RedisKey::UNIT_BOT_VOICE_SET . $user_id;
        switch ($user_id)
        {
            case "oLIAK0hNsAVzyxh3pmWLSNfNibwc":
                $this->bot_id = self::BOT_BOY;
                Redis::set($key, self::VOICE_MAN);
                break;
            case "oLIAK0gFuUw_yGsUdBxPxiXmJKeo":
                Redis::set($key, self::VOICE_WOMAN);
                $this->bot_id = self::BOT_GIRL;
                break;
            default:
                $this->bot_id = self::BOT_COMMON;
                break;
        }
    }

}
