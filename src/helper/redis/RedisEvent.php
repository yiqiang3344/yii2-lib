<?php
namespace yiqiang3344\yii2_lib\helper\redis;
use yii\base\Event;
class RedisEvent extends Event
{
    public $command = '';
}