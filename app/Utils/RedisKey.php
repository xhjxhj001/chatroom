<?php
/**
 * Created by PhpStorm.
 * User: v_xuhuanju
 * Date: 2018/7/3
 * Time: 16:28
 */

namespace App\Utils;

class RedisKey {

    //*** UNIT REDIS KEYS ***//
    const UNIT_SESSION_EXPIRE = 60;

    // 用户机器人 session
    const UNIT_BOT_SESSION = "unit_bot_session:openid:";
    // 用户机器人 回复方式
    const UNIT_BOT_MODE_SET = "unit_bot_set:openid:";
    // 用户机器人 闲聊开关
    const UNIT_BOT_CHAT_SET = "unit_bot_chat_set:openid:";
    // 用户机器人 语音设置
    const UNIT_BOT_VOICE_SET = "unit_bot_voice_set:openid:";

    /**
     * 开启春联创作
     */
    const START_COUPLETS_MODE = "baidu_couplets_mode:openid:";

    /**
     * 百度 unit token
     */
    const BAIDU_UNIT_TOKEN = "baidu_unit_token";

    /**
     * 百度 语音 token
     */
    const BAIDU_VOICE_TOKEN = "baidu_voice_token";

}