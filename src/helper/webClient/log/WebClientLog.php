<?php

namespace yiqiang3344\yii2_lib\helper\webClient\log;

use yiqiang3344\yii2_lib\helper\Time;
use yii\base\Model;

/**
 * web访问日志
 * Class WebClientLog
 * @package common\logging
 */
class WebClientLog extends Model
{
    public $tag;
    public $url;
    public $start_time;
    public $data;
    public $head;
    public $header;
    public $options;
    public $end_time;
    public $result;
    public $response_code;

    public function start($tag, $url, $body, $header, $options)
    {
        $this->tag = $tag;
        $this->url = $url;
        $this->start_time = Time::microtime();
        $this->data = $body;
        $this->header = $header;
        $this->options = $options;
    }

    public function setResult($result, $responseCode = 200)
    {
        $this->end_time = Time::microtime();
        $this->result = $result;
        $this->response_code = $responseCode;
    }

    public function writeLog()
    {
        self::log([
            'request' => [
                'time' => Time::nowWithMicros($this->start_time),
                'info' => [
                    'url' => $this->url,
                    'header' => $this->header ?: [],
                    'options' => $this->options ?: [],
                    'body' => $this->data ?: [],
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
            'type' => 'web_client',
            'created_time' => date('Y-m-d H:i:s'),
            'debug' => $traces, //写日志位置
        ];
        \Yii::info($data, 'web_client');
    }
}