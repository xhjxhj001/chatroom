<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Listeners\BaseListener;
use App\Utils\RedisKey;
use Illuminate\Support\Facades\Redis;

class getToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:getToken';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get latest token';

    /**
     * curl base request function
     * @var BaseListener
     */
    protected $curl;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->curl = new BaseListener();

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->refreshUnitToken();
        $this->refreshVoiceToken();
        echo "success";
    }

    protected function setRedis($key, $value)
    {
        Redis::set($key, $value);
    }

    protected function refreshUnitToken()
    {
        $unit_key = getenv("BAIDU_APP_UNIT_KEY");
        $unit_secret = getenv("BAIDU_APP_UNIT_SECRET");
        $params = array(
            'grant_type' => 'client_credentials',
            'client_id' => $unit_key,
            'client_secret' => $unit_secret
        );
        $url_unit = "https://aip.baidubce.com/oauth/2.0/token?";
        foreach ($params as $key => $value){
            $url_unit = $url_unit . $key . "=" . $value . "&";
        }
        trim("&", $url_unit);
        $res = $this->curl->request_get($url_unit);
        $key = RedisKey::BAIDU_UNIT_TOKEN;
        if(isset($res['access_token'])){
            $this->setRedis($key, $res['access_token']);
        }
    }

    protected function refreshVoiceToken()
    {
        $voice_key = getenv("BAIDU_APP_VOICE_KEY");
        $voice_secret = getenv("BAIDU_APP_VOICE_SECRET");
        $params = array(
            'grant_type' => 'client_credentials',
            'client_id' => $voice_key,
            'client_secret' => $voice_secret
        );
        $url_voice = "https://openapi.baidu.com/oauth/2.0/token?";
        foreach ($params as $key => $value){
            $url_voice = $url_voice . $key . "=" . $value . "&";
        }
        trim("&", $url_voice);
        $res = $this->curl->request_get($url_voice);
        $key = RedisKey::BAIDU_VOICE_TOKEN;
        if(isset($res['access_token'])){
            $this->setRedis($key, $res['access_token']);
        }
    }
}
