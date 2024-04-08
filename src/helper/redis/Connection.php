<?php
namespace yiqiang3344\yii2_lib\helper\redis;
use yii\redis\Connection as C;

class Connection extends C
{
    const EVENT_BEFORE_EXECUTE = 'beforeExecute';
    const EVENT_AFTER_EXECUTE = 'afterExecute';
    public function executeCommand($name, $params = [])
    {
        $event = new RedisEvent();
        $event->command = $name;
        $this->trigger(static::EVENT_BEFORE_EXECUTE, $event);
        $res = parent::executeCommand($name, $params);
        $this->trigger(static::EVENT_AFTER_EXECUTE, $event);
        return $res;
    }
}