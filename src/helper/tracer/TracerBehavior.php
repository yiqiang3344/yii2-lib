<?php
/**
 * User: ljj
 */
namespace yiqiang3344\yii2_lib\helper\tracer;

use yii\base\Behavior;
use yii\base\Application;
use \Yii;

/**
 * 全链路追踪启动类
 * User: ljj
 */
class TracerBehavior extends Behavior
{
    public function init()
    {
        Yii::$app->on(Application::EVENT_BEFORE_ACTION, function ($event){
            Tracer::instance();
        });
    }
}