<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/11
 * Time: 11:22 AM
 */

namespace yiqiang3344\yii2_lib\helper\redis;

use yii\base\Model;
use yii\redis\Connection;

/**
 * 通用Redis
 * User: sidney
 * Date: 2019/8/29
 */
class Redis extends Model
{
    protected static $redisName = 'redis';
    protected static $redis;

    /**
     * @param bool $refresh
     * @return Connection
     */
    public static function instance($refresh = false)
    {
        if ($refresh || !static::$redis instanceof Connection) {
            $redisName = static::$redisName;
            static::$redis = \Yii::$app->$redisName;
        }
        return static::$redis;
    }

    /**
     * redis 断线重连
     */
    public static function reconnect()
    {
        if (static::instance()->getIsActive() === false) {
            Redis::instance()->close();
            Redis::instance()->open();
        }
    }
}