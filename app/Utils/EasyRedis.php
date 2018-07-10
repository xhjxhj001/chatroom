<?php
/**
 * Created by PhpStorm.
 * User: v_xuhuanju
 * Date: 2018/7/3
 * Time: 11:38
 */
namespace App\Utils;

use Illuminate\Support\Facades\Redis;

class EasyRedis
{

    /**
     * 获取 string key 的值
     * @param $key
     * @return mixed
     */
    public static function get($key)
    {
        $res = Redis::get($key);
        return $res;
    }

    /**
     * 设置 string key 的值
     * @param string $key 键名
     * @param string $value 键值
     * @param int $expire 过期时间
     * @return mixed
     */
    public static function set($key, $value, $expire = 3600)
    {
        $res = Redis::set($key, $value);
        Redis::expire($key, $expire);
        return $res;
    }

}