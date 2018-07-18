<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ThirdPartAPI extends BaseListener
{
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
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        //
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
        if($res['error_code'] != 0){
            return "查询失败";
        }
        switch ($type)
        {
            case "today":
                $all = $this->score2star($res['all']);
                $health = $this->score2star($res['health']);
                $love = $this->score2star($res['love']);
                $money = $this->score2star($res['money']);
                $work = $this->score2star($res['work']);
                $response = "{$name}今日运势" ."\n" .
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
                $response = "{$name}明日运势" ."\n" .
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
                $response = "{$name}本周运势" ."\n" .
                    $res['love'] . "\n" .
                    $res['money'] . "\n" .
                    $res['work'];
                break;
            case "month":
                $response = "{$name}本月运势" ."\n" .
                    "综合运势：{$res['all']}" . "\n" .
                    "爱情运势：{$res['love']}" . "\n" .
                    "财运运势：{$res['money']}" . "\n" .
                    "健康运势：{$res['health']}" . "\n" .
                    "工作运势：{$res['work']}";
                break;
            case "year":
                $response = "{$name} {$res['year']}年度运势" ."\n" .
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
        if(empty($score)){
            return $str;
        }else{
            $star = "★";
            $empty_star = "☆";
            $score = round(substr($score, 0, -1) / 20);
            $empty_score = 5-$score;
            for($i = 0; $i < $score; $i++){
                $str = $str . $star;
            }
            for($i = 0; $i < $empty_score; $i++){
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
