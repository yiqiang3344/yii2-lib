<?php

namespace yiqiang3344\yii2_lib\helper\green\log;

use yiqiang3344\yii2_lib\helper\Time;

/**
 * 安全内容请求日志
 */
class GreenLog
{
    public $platform;
    public $type;
    public $tag;
    public $data;
    public $start_time;
    public $end_time;
    public $result;
    public $response_code;

    public function start($tag, $platform, $type, $data)
    {
        $this->tag = $tag;
        $this->platform = $platform;
        $this->start_time = Time::microtime();
        $this->type = $type;
        $this->data = $data;
        return $this;
    }

    public function setResult($result, $responseCode = 200)
    {
        $this->end_time = Time::microtime();
        $this->result = $result;
        $this->response_code = $responseCode;
        return $this;
    }

    public function writeLog()
    {
        self::log([
            'request' => [
                'time' => Time::nowWithMicros($this->start_time),
                'info' => [
                    'platform' => $this->platform,
                    'type' => $this->type,
                    'data' => $this->data,
                ],
            ],
            'response' => [
                'time' => Time::nowWithMicros($this->end_time),
                'info' => $this->result ?: [],
                'code' => $this->response_code ?: 200,
            ],
            'response_time' => Time::getSubMicroTime($this->end_time, $this->start_time),
        ], $this->tag);
    }

    /**
     * @param $message
     * @param string $messageTag
     */
    public static function log($message, $messageTag = '')
    {
        $ts = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        array_pop($ts); // remove the last trace since it would be the entry script, not very useful
        $traces = [];
        foreach ($ts as $trace) {
            if (isset($trace['file'], $trace['line']) && strpos($trace['file'], YII2_PATH) !== 0) {
                unset($trace['object'], $trace['args']);
                $traces[] = $trace;
            }
        }
        $data = [
            'message_tag' => $messageTag,
            'message' => $message,
            'created_time' => date('Y-m-d H:i:s'),
            'debug' => $traces, //写日志位置
        ];
        \Yii::info($data, 'green');
    }
}