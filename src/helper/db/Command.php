<?php

namespace yiqiang3344\yii2_lib\helper\db;

use yiqiang3344\yii2_lib\helper\event\Event;
use yiqiang3344\yii2_lib\helper\event\events\SlowSqlEvent;
use yiqiang3344\yii2_lib\helper\Time;

/**
 * 自定义命令处理类
 *
 * 支持慢sql处理
 *
 */
class Command extends \yii\db\Command
{
    protected function internalExecute($rawSql)
    {
        $microtime = microtime();
        parent::internalExecute($rawSql);
        $cost = Time::getSubMicroTime(microtime(), $microtime);
        Event::event(new SlowSqlEvent([
            'sql' => $rawSql,
            'cost' => $cost,
            'slowSqlTime' => $this->db->slowSqlTime ?? null,
        ]));
    }
}