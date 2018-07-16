<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

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

    public function checkConstellation($name, $type)
    {
        $key = config("juhe.keys.constellation");
        $url = "http://web.juhe.cn:8080/constellation/getAll?key={$key}&consName={$name}&type={$type}";
        $res = $this->request_get($url);
        if(isset($res['error_code'])){
            return "查询失败";
        }
        switch ($type)
        {
            case "today":
                $all = $this->score2star($res['all']);
                $response = "综合运势：";

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
            for($i = 0; $i < 5; $i++){
                $str = $str . $star;
                $score = $score - 1;
                if($score < 0){
                    $str = $str . $empty_star;
                }
            }
            return $str;
        }
    }
}
