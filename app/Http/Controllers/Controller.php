<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function success($data, $errno = 0)
    {
        $data = array(
            'errno' => $errno,
            'data' => $data
        );
        return json_encode($data);
    }

    public function fail($errno, $errmsg)
    {
        $data = array(
            'errno' => $errno,
            'errmsg' => $errmsg
        );
        return json_encode($data);
    }
}
