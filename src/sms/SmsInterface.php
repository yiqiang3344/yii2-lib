<?php

namespace xyf\lib\sms;


use yii\redis\Connection;

/**
 *
 * User: sidney
 * Date: 2020/4/10
 * @since 1.0.19
 */
interface SmsInterface
{
    /**
     * @return Connection
     */
    public function getRedis();

    public function send($data);

    public function syncStatus($data);

    public function pushSyncQueue($data);

    public function calSendAvgTime($time);

    public function calSyncAvgTime($time);

    public function exceptionInfo(\Throwable $e);

    public function beforeSend($data);

    public function AfterSend($data, $ret);

    public function limitCheck($data);

    public function templateCheck($data);

    public function matchChannel($data);

    public function getChannel($channel);

    public function updateStatusOvertime($data);

    public function log($message, $tag);

    public function generateKey($key, $params = []);

    public function getSendMaxProcessNum();

    public function getSendExpectAvgTime();

    public function getSyncMaxProcessNum();

    public function getSyncExpectAvgTime();
}