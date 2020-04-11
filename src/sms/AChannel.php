<?php
namespace yiqiang3344\yii2_lib\sms;

/**
 *
 * User: sidney
 * Date: 2020/4/10
 * @since 1.0.19
 */
abstract class AChannel
{
    abstract public function getName();

    abstract public function sendSms($data);

    abstract public function syncStatus($data);
}