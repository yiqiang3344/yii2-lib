<?php
/**
 * Created by PhpStorm.
 * User: sidney
 * Date: 2020/4/8
 * Time: 12:36 PM
 */

namespace xyf\lib\sms;


abstract class AChannel
{
    abstract public function getName();

    abstract public function sendSms($data);

    abstract public function syncStatus($data);
}