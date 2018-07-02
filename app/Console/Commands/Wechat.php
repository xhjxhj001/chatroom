<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use EasyWeChat\Kernel\Messages\Text;

class Wechat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:sendWeather';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send weather report';

    protected $app;

    public function __construct()
    {
        parent::__construct();
        $this->app = app('wechat.official_account');
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $message = $this->checkWeather("北京市");
        $message = new Text($message);
        $user_list = $this->getUserList();
        foreach ($user_list as $user){
            $this->app->customer_service->message($message)->to($user)->send();
        }
    }

    protected function getUserList()
    {
        $user_list = $this->app->user->list($nextOpenId = null);
        $user_openids = $user_list['data']['openid'];
        return $user_openids;
    }

    /**
     * 查询天气
     * @param $city
     * @param int $date
     * @return string
     */
    protected function checkWeather($city, $date = 0)
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
        }else{
            $response = "对不起，找不到 " .$city . "的天气情况";
        }
        return $response;
    }

    /**
     * 模拟post进行url请求
     * @param string $url
     * @param string $param
     * @return array|bool $data
     **/
    protected function request_get($url = '')
    {
        Log::info("curl start url: $url");
        if (empty($url)){
            return false;
        }
        $postUrl = $url;

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        $data = curl_exec($ch);//运行curl
        Log::info("curl end result: $data");
        $data = json_decode($data, true);
        curl_close($ch);
        return $data;
    }
}
