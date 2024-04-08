<?php

namespace yiqiang3344\yii2_lib\helper\webClient;

use yiqiang3344\yii2_lib\helper\Time;
use yii\base\Model;
use yii\httpclient\Exception;

/**
 *
 * User: sidney
 * Date: 2020/1/7
 */
class XyfWebClient extends Model
{
    /**
     * 业务系统接口统一请求方法
     * @param $url
     * @param string|array $body
     * @param array $head
     * @param array $header
     * @param array $options
     * @param bool $ignoreLog
     * @return array
     * @throws \yii\base\Exception
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public static function requestXyf($url, $body, $head = [], $header = [], $options = [], $ignoreLog = false)
    {
        //设置header
        if (!$header) {
            $header = [
                'content-type' => 'application/x-www-form-urlencoded'
            ];
        }
        //设置配置项
        if (!$options) {
            $options = [
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 5,
            ];
        }
        //设置信用飞服务接口参数结构
        $head = array_merge([
            'requester' => \Yii::$app->id,
            'user_id' => \Yii::$app->user->id ?? '',
            'timestamp' => Time::getMicrotime(),
        ], $head);
        $data = [
            'head' => $head,
            'body' => $body,
        ];
        $data = ['request_data' => json_encode($data, JSON_UNESCAPED_UNICODE)];
        $ret = WebClient::post($url, $data, $header, $options, $ignoreLog);
        if ($ret === false) {
            throw new Exception('请求失败', -21);
        }
        return $ret;
    }
}