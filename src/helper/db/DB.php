<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/11
 * Time: 11:33 AM
 */

namespace yiqiang3344\yii2_lib\helper\db;


use yii\base\Model;
use yii\db\Connection;

/**
 * 数据库连接类
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
 */
class DB extends Model
{
    /**
     * 默认DB
     * @return \yii\db\Connection
     */
    public static function default()
    {
        $db = \Yii::$app->db;
        return $db;
    }

    /**
     * @param $callback
     * @param Connection|null $connection
     * @return null
     * @throws \Throwable
     */
    public static function transaction($callback, Connection $connection = null){
        return DbTransaction::run($callback, $connection);
    }
}