<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ThirdPartAPI extends BaseListener
{
    protected $power_action_list = array(
        "打开" => "power_on",
        "开开" => "power_on",
        "关闭" => "power_off",
        "关上" => "power_off"
    );

    protected $power_name_list = array(
        "空调" => "air_condition",
        "床头灯" => "bed_lamp_1"
    );

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object $event
     * @return void
     */
    public function handle($event)
    {
        //
    }

    /**
     * 电源控制
     * @param $action
     * @param $name
     * @return string
     */
    public function powerControl($action, $name)
    {
        $action_value = $this->power_action_list[$action];
        Log::info("[power_control]" . $action_value . $name);
        $name_value = $this->power_name_list[$name];
        Redis::set('tmp', $action_value . '_' . $name_value);
        return $name . "已" . $action;
    }

    /**
     * 星座查询API
     * @param $name
     * @param $type
     * @return string
     */
    public function checkConstellation($name, $type)
    {
        $key = config("juhe.keys.constellation");
        $url = "http://web.juhe.cn:8080/constellation/getAll?key={$key}&consName={$name}&type={$type}";
        $res = $this->request_get($url);
        if ($res['error_code'] != 0) {
            return "查询失败";
        }
        switch ($type) {
            case "today":
                $all = $this->score2star($res['all']);
                $health = $this->score2star($res['health']);
                $love = $this->score2star($res['love']);
                $money = $this->score2star($res['money']);
                $work = $this->score2star($res['work']);
                $response = "{$name}今日运势" . "\n" .
                    "综合运势：{$all}" . "\n" .
                    "爱情指数：{$love}" . "\n" .
                    "财运指数：{$money}" . "\n" .
                    "健康指数：{$health}" . "\n" .
                    "工作指数：{$work}" . "\n" .
                    "速配星座：{$res['QFriend']}" . "\n" .
                    "幸运数字：{$res['number']}" . "\n" .
                    "幸运颜色：{$res['color']}" . "\n" .
                    "星座寄语：{$res['summary']}";
                break;
            case "tomorrow":
                $all = $this->score2star($res['all']);
                $health = $this->score2star($res['health']);
                $love = $this->score2star($res['love']);
                $money = $this->score2star($res['money']);
                $work = $this->score2star($res['work']);
                $response = "{$name}明日运势" . "\n" .
                    "综合运势：{$all}" . "\n" .
                    "爱情指数：{$love}" . "\n" .
                    "财运指数：{$money}" . "\n" .
                    "健康指数：{$health}" . "\n" .
                    "工作指数：{$work}" . "\n" .
                    "速配星座：{$res['QFriend']}" . "\n" .
                    "幸运数字：{$res['number']}" . "\n" .
                    "幸运颜色：{$res['color']}" . "\n" .
                    "星座寄语：{$res['summary']}";
                break;
            case "week":
                $response = "{$name}本周运势" . "\n" .
                    $res['love'] . "\n" .
                    $res['money'] . "\n" .
                    $res['work'];
                break;
            case "month":
                $response = "{$name}本月运势" . "\n" .
                    "综合运势：{$res['all']}" . "\n" .
                    "爱情运势：{$res['love']}" . "\n" .
                    "财运运势：{$res['money']}" . "\n" .
                    "健康运势：{$res['health']}" . "\n" .
                    "工作运势：{$res['work']}";
                break;
            case "year":
                $response = "{$name} {$res['year']}年度运势" . "\n" .
                    "综合运势：{$res['mima']['text'][0]}" . "\n" .
                    "幸运石：{$res['luckeyStone']}" . "\n" .
                    "星座寄语：{$res['mima']['info']}";
                break;

        }
        return $response;

    }

    /**
     * 分数转换成星
     * @param $score
     * @return string
     */
    protected function score2star($score)
    {
        $str = "";
        if (empty($score)) {
            return $str;
        } else {
            $star = "★";
            $empty_star = "☆";
            $score = round(substr($score, 0, -1) / 20);
            $empty_score = 5 - $score;
            for ($i = 0; $i < $score; $i++) {
                $str = $str . $star;
            }
            for ($i = 0; $i < $empty_score; $i++) {
                $str = $str . $empty_star;
            }
            return $str;
        }
    }

    public function buyMovieTickets($cinemaId)
    {
        $url = "https://m.maoyan.com/shows/{$cinemaId}?from=mmweb";
        return $url;
    }

}
