<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class BaseListener
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
     * 模拟post进行url请求
     * @param string $url 请求url
     * @param string $param 请求参数
     * @return array|bool $data 返回结果
     **/
    protected function request_post($url = '', $param = '')
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        $postUrl = $url;
        Log::info("curl-request-url:" . $url);
        $curlPost = $param;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        $data = json_decode($data, true);
        curl_close($ch);
        return $data;
    }

    /**
     * 模拟get进行url请求
     * @param string $url 请求url
     * @param bool $is_file 返回结果是否为二进制流文件
     * @param string $filename 文件名
     * @return bool|mixed 返回结果
     */
    protected function request_get($url = '', $is_file = false , $filename = "")
    {
        if (empty($url)){
            return false;
        }
        $postUrl = $url;
        Log::info($url);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        $data = curl_exec($ch);//运行curl
        if($is_file == false){
            $data = json_decode($data, true);
        }else{
            $res = file_put_contents($filename, $data);
            Log::info($res);
        }
        curl_close($ch);
        return $data;
    }

}
