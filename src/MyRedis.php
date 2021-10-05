<?php
namespace Fuzqing\WpDevUtils;

class MyRedis
{
    private static \Redis|null $redis_instance = null;

    private function __construct(){}

    private function __clone(){}

    public static function getRedisClient($host,$port,$password,$db): \Redis
    {
        if (!self::$redis_instance) {
            $redis = new \Redis();
            try {
                $redis->connect($host, $port);
                if (!empty($password)) {
                    $redis->auth($password);
                }
                $redis->select($db);
            } catch (\Exception $e) {
                die( "Cannot connect to redis server : ".$e->getMessage() );
            }
            self::$redis_instance = $redis;
        }

        return self::$redis_instance;
    }


}