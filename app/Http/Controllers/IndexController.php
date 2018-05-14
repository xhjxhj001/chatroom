<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Redis;

class IndexController extends BaseController
{
    public function index()
    {
	$data = Redis::get('tmp');
	if(!empty($data)){
    	    return json_encode(array("errno" => 0, "errmsg" => "success", "data" => $data));   
	}else{
	    $data = Redis::set('tmp', 'xlp');
	    return json_encode(array("errno" => 0, "errmsg" => "success", "data" => 'insert to redis success'));
	}
    }
}
