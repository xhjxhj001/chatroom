<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderController extends BaseController
{
    
    private $key = "IphoneX:count";
    
    public function initorder()
    {
    	$key = "IphoneX:count";
    	$res = Redis::set($key, 100);
	return "success,$res";
    }

    public function makeorder()
    {
	#date_default_timezone_set('PRC');
	$res = Redis::decr($this->key);
	if($res >= 0){
	    $message = "order success! order_num:[".$res."]" . date("Y-m-d H:i:s",time())."\n";
	    Log::notice($message);
	    return "order" . $res . " success";
	}else{
	    $message = "order failed!".date("Y-m-d H:i:s",time())."\n";
	    Log::warning($message);
	    return "order failed";
	}
    }

    public function makeOrderByMysql()
    {
	$order = DB::table('order')->first();
	if($order->order <= 0){
	    return false;
	}
	$res = DB::table('order')->where('id',1)->update(['order' => $order->order -1]);
	Log::notice("reading mysql success".$res);
	return json_encode($res);
    }

    public function getorder()
    {
	$res = Redis::get($this->key);
	return $res;
    }

}
