<?php

namespace yiqiang3344\yii2_lib\helper\log;


use \common\helper\Env;

/**
 * 请求控制器集成请求日志
 */
trait TEncryptArgs
{
    public function beforeAction($action)
    {
        $ret = parent::beforeAction($action);
        $this->request = \Yii::$app->request;
        if ($this->request->getMethod() == 'GET') {
            $body = $this->request->getQueryParams();
        } else {
            $body = $this->request->getBodyParams();
            $body['args'] = Env::getArgs();
        }
        $header = [];
        foreach ($this->request->headers->toArray() as $k => $row) {
            $header[$k] = implode(' ', $row);
        }
        $this->addLog('request', ['url' => $this->request->getPathInfo(), 'header' => $header, 'body' => $body]);
        return $ret;
    }
}