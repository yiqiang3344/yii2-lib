<?php

namespace yiqiang3344\yii2_lib\helper\log;


use yiqiang3344\yii2_lib\helper\CodeMessage;
use yiqiang3344\yii2_lib\helper\EnvV2;
use yiqiang3344\yii2_lib\helper\Time;
use yii\web\Response;
use yiqiang3344\yii2_lib\helper\tracer\Tracer;

/**
 * 请求控制器集成请求日志
 */
trait TAccessLog
{
    protected $access_log = [];
    /** @var CodeMessage */
    public $codeMessage; //需要再控制器中初始化

    public function addLog($type, $logContent)
    {
        $this->access_log[$type] = [
            'time' => microtime(),
            'info' => $logContent,
        ];
        return $this;
    }

    /**
     * @return $this
     */
    public function logFlush()
    {
        if (empty($this->access_log)) {
            return $this;
        }
        $log = [
            'status_code' => $this->access_log['status_code']['info'],
            'response_time' => max(0, Time::getSubMicroTime($this->access_log['response']['time'] ?? microtime(), $this->access_log['request']['time'] ?? microtime())),
            'request' => [],
            'response' => [],
            'origin_body' => $this->access_log['origin_body']['info'] ?? '',
            'origin_response' => $this->access_log['origin_response']['info'] ?? [],
        ];
        if (isset($this->access_log['request'])) {
            $log['request'] = [
                'time' => Time::nowWithMicros($this->access_log['request']['time'] ?? null),
                'info' => $this->access_log['request']['info'],
            ];
        }
        if (isset($this->access_log['response'])) {
            $log['response'] = [
                'time' => Time::nowWithMicros($this->access_log['response']['time'] ?? null),
                'info' => $this->access_log['response']['info'],
            ];
        }
        if (isset($this->access_log['error'])) {
            $log['error'] = [
                'time' => Time::nowWithMicros($this->access_log['error']['time'] ?? null),
                'info' => $this->access_log['error']['info'],
            ];
        }
        $log['trace_id'] = Tracer::instance()->getTraceId();
        \Yii::info($log, 'access');
        $this->access_log = [];
        return $this;
    }

    public function __destruct()
    {
        $this->logFlush();
    }

    public function beforeAction($action)
    {
        $this->request = \Yii::$app->request;
        $header = [];
        foreach ($this->request->headers->toArray() as $k => $row) {
            $header[$k] = implode(' ', $row);
        }
        $this->addLog('request', ['url' => $this->request->getPathInfo(), 'method' => $this->request->getMethod(), 'header' => $header, 'body' => $this->request->getMethod() == 'GET' ? $this->request->getQueryParams() : $this->request->getBodyParams()]);
        $this->addLog('origin_body', $this->request->getBodyParams());
        return parent::beforeAction($action);
    }

    public function runAction($id, $params = [])
    {
        try {
            return parent::runAction($id, $params);
        } catch (\Throwable $e) {
            $response = $this->codeMessage::getResponseFromException($e);
            $this->addLog('response', $response->data);
            $this->addLog('origin_response', EnvV2::getOriginResponse());
            $this->addLog('status_code', $response->statusCode);
            $this->addLog('error', '[code ' . $e->getCode() . ']' . ((string)$e));
            $this->logFlush();
            throw $e;
        }
    }

    /**
     * @param \yii\base\Action $action
     * @param mixed $result
     * @return mixed
     * @throws \yii\base\Exception
     */
    public function afterAction($action, $result)
    {
        if ($result instanceof Response) {
            $this->addLog('response', $result->data);
            $this->addLog('origin_response', EnvV2::getOriginResponse());
            $this->addLog('status_code', $result->statusCode);
        } else {
            $this->addLog('response', $result);
            $this->addLog('origin_response', EnvV2::getOriginResponse());
            $this->addLog('status_code', \Yii::$app->response->statusCode);
        }
        $this->logFlush();
        return parent::afterAction($action, $result);
    }
}