<?php

namespace yiqiang3344\yii2_lib\helper\webClient;

use yiqiang3344\yii2_lib\helper\ArrayHelper;
use yiqiang3344\yii2_lib\helper\config\Config;
use yiqiang3344\yii2_lib\helper\Env;
use yiqiang3344\yii2_lib\helper\webClient\log\WebClientLog;
use yiqiang3344\yii2_lib\helper\webClient\log\WebClientTimeoutLog;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\httpclient\Request;

/**
 * 支持http状态码不为20x时，一样可以返回响应json的解析
 * User: xinfei
 * Date: 2021/6/10
 */
class WebClientV2 extends WebClient
{
    /**
     *
     * @param $request
     * @param $log
     * @param $url
     * @param null $data
     * @return mixed
     * @throws Exception
     */
    public static function handleSend(Request $request, WebClientLog $log, $url, $data = null)
    {
        $request->setUrl($url);
        if (!is_null($data)) {
            $request->setData($data);
        }
        $response = $request->send();
        try {
            $data = $response->getData();
            //如果返回内容可以解析为数组，则返回数组，其他都返回为false
            $_result = $result = $data;
            if (empty($data)) {
                $result = $response->getContent();
                $_result = false;
            }
        } catch (\Throwable $e) {
            $result = $response->getContent();
            $_result = false;
        }
        $log->setResult($result, $response->getStatusCode());
        static::$httpStatusCode = $response->getStatusCode();
        static::$result = $response->getContent();
        return $_result;
    }
}