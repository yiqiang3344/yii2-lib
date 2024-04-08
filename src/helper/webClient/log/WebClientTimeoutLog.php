<?php
namespace yiqiang3344\yii2_lib\helper\webClient\log;

use yii\base\Model;

/**
 * web访问超时日志
 * Class WebClientTimeoutLog
 * @package common\logging
 */
class WebClientTimeoutLog extends Model
{
    public static function log($url, $data, $header, $options, $response)
    {
        $data = [
            'url' => $url,
            'data' => $data,
            'header' => $header,
            'options' => $options,
            'response' => $response,
        ];
        \Yii::info($data, 'web_client_timeout');
    }
}