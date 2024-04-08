<?php

namespace yiqiang3344\yii2_lib\helper\log;

use yiqiang3344\yii2_lib\helper\Env;
use Yii;
use yii\helpers\VarDumper;
use yii\log\Logger;

/**
 * Class FileTarget
 */
class JsonFileTarget extends FileTarget
{
    protected function getExtMessage()
    {
        return [
            'pid' => getmypid(),
            'memory_usage' => memory_get_usage(),
            'memory_usage_mb' => round(memory_get_usage()/1024/1024, 2),
        ];
    }

    /**
     * @param array $message
     * @return string
     * @throws \Exception
     * @throws \Throwable
     */
    public function formatMessage($message)
    {
        if ($this->ignoreLog) {
            return '';
        }
        list($info, $level, $category, $timestamp) = $message;
        $level = Logger::getLevelName($level);
        if (!is_string($info)) {
            // exceptions may not be serializable if in the call stack somewhere is a Closure
            if ($info instanceof \Throwable || $info instanceof \Exception) {
                $info = (string)$info;
            }
        }
        $traces = [];
        if (isset($message[4])) {
            foreach ($message[4] as $trace) {
                $traces[] = "in {$trace['file']}:{$trace['line']}";
            }
        }

        $ret = [
            'time' => $this->getTime($timestamp),
        ];

        if (is_array($info) && isset($info['message_tag'])) {
            $ret['message_tag'] = $info['message_tag'];
            unset($info['message_tag']);
            $ret = array_merge($ret, $info);
        } elseif (is_array($info) or is_string($info)) {
            $ret['message'] = $info;
        } else {
            $ret['message'] = VarDumper::export($info);
        }

        $ret = array_merge($ret, $this->getMessagePrefix($message),$this->getExtMessage(), [
            'level' => $level,
            'application' => Yii::$app->id,
            'host_name' => gethostname(),
            'category' => $category,
            'ip' => Env::getIp(),
            'debug' => $traces,
        ]);

        return json_encode($ret, JSON_UNESCAPED_UNICODE);
    }
}
