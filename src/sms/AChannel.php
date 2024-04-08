<?php
namespace yiqiang3344\yii2_lib\sms;

/**
 *
 * User: sidney
 * Date: 2020/4/10
 */
abstract class AChannel
{
    abstract public function getName();

    abstract public function sendSms($data);

    abstract public function syncStatus($data);
}