<?php

namespace yiqiang3344\yii2_lib\helper\db;

use Exception;
use Throwable;
use yii\db\Connection;

/**
 * 数据库事务类，以闭包的形式来使用事务
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
 */
class DbTransaction
{
    private static $connections = [];
    private static $counts = [];

    /**
     * @param $callback
     * @param Connection|null $connection
     * @return null
     * @throws Throwable
     */
    public static function run($callback, Connection $connection = null)
    {
        $connection = $connection ?: DB::default();
        $index = array_search($connection, self::$connections, true);
        if ($index === false) {
            self::$connections[] = $connection;
            self::$counts[] = 0;
            end(self::$connections);
            $index = key(self::$connections);
        }
        $result = null;
        $count = self::$counts[$index];
        ++self::$counts[$index];
        try {
            $transaction = $connection->getTransaction();
            if ($count === 0) {
                $transaction = $connection->beginTransaction();
            }
            $e = null;
            try {
                $result = $callback();
                if ($count === 0) {
                    $transaction->commit();
                }
            } catch (Exception $e) {
            } catch (Throwable $e) {
            }

            if ($e !== null) {
                if ($count === 0) {
                    $transaction->rollback();
                }
                throw $e;
            }
        } finally {
            if ($count === 0) {
                unset(self::$counts[$index]);
                unset(self::$connections[$index]);
            } else {
                --self::$counts[$index];
            }
        }
        return $result;
    }
}
