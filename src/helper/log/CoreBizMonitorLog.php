<?php

namespace yiqiang3344\yii2_lib\helper\log;

use yii\base\Model;

/**
 * 核心业务监控日志
 * Class AdminTraceLog
 * @package common\logging
 */
class CoreBizMonitorLog extends Model
{
    /**
     * 记日志
     * @param $message
     * @param string $message_tag
     */
    public function log($message, $message_tag)
    {
        if (!$message_tag) {
            return;
        }
        $params = ['message' => $message];
        $params['message_tag'] = $message_tag;
        \Yii::info($params, 'core_biz_monitor');
    }
}