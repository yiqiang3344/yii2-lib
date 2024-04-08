<?php


namespace yiqiang3344\yii2_lib\helper\event\events;


use yii\base\Event;

/**
 */
class SlowSqlEvent extends Event
{
    public $sql;
    public $cost;
    public $slowSqlTime;
}