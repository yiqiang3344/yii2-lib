<?php


namespace yiqiang3344\yii2_lib\helper\event;


use yii\base\Event;

/**
 */
interface ListenerInterface
{
    //异常不要抛出，不要中断正常业务，确认可以中断业务，才抛出
    public function handle(Event $event);
}