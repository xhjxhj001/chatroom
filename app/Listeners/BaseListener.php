<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

class BaseListener
{

    /**
     * 模拟post进行url请求
     * @param string $url 请求url
     * @param string $param 请求参数
     * @return array|bool $data 返回结果
     **/
    public function request_post($url = '', $param = '')
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        $postUrl = $url;
        Log::info("[curl-request][url]:" . $url . "[body]:" . $param);
        $curlPost = $param;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        Log::info("[curl-request][response]: " . $data);
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
    public function request_get($url = '', $is_file = false , $filename = "")
    {
        if (empty($url)){
            return false;
        }
        $postUrl = $url;
        Log::info("[curl-request][url]:" . $url);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        $data = curl_exec($ch);//运行curl
        if($is_file == false){
            Log::info("[curl-request][response]:" . $data);
            $data = json_decode($data, true);
        }else{
            file_put_contents($filename, $data);
        }
        curl_close($ch);
        return $data;
    }

}
