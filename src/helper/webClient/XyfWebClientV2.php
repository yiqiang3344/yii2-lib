<?php

namespace yiqiang3344\yii2_lib\helper\webClient;

use yiqiang3344\yii2_lib\helper\encrypt\Encrypt;
use yiqiang3344\yii2_lib\helper\Time;
use yii\base\Model;
use yii\httpclient\Exception;

/**
 *
 * User: xinfei
 * Date: 2021/6/10
 */
class XyfWebClientV2 extends Model
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
        $ret = WebClientV2::post($url, $data, $header, $options, $ignoreLog);
        if ($ret === false) {
            throw new Exception('请求失败', -21);
        }
        return $ret;
    }

    /**
     * v3版本请求统一方法（基于rsa公私钥）
     * @param string $url
     * @param string $method 不带版本号
     * @param string $ua
     * @param string $pubKey
     * @param array $args
     * @param array $header
     * @param array $options
     * @param bool $ignoreLog
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public static function requestV3(string $url, string $method, string $ua, string $pubKey, array $args, array $header = [], array $options = [], bool $ignoreLog = false)
    {
        $encryptKey = mt_rand(1000000000000000, 9999999999999999);
        $header = array_merge($header, [
            'encrypt-key' => Encrypt::rsaPubEncrypt($encryptKey, $pubKey),
        ]);
        $args = Encrypt::aesEcbEncrypt(json_encode($args, JSON_UNESCAPED_UNICODE), $encryptKey);
        $body = [
            'ua' => $ua,
            'args' => $args,
            'sign' => Encrypt::getSignByUa($ua, $encryptKey, $args, $method),
            'timestamp' => Time::time(),
        ];
        $url = $url . '/' . $method;
        return WebClientV2::post($url, $body, $header, $options, $ignoreLog);
    }

    /**
     * v3版本带解密响应数据的请求统一方法（基于rsa公私钥）
     * @param string $url
     * @param string $method 不带版本号
     * @param string $ua
     * @param string $pubKey
     * @param array $args
     * @param array $header
     * @param array $options
     * @param bool $ignoreLog
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public static function requestV3WithDecryptResponse(string $url, string $method, string $ua, string $pubKey, array $args, array $header = [], array $options = [], bool $ignoreLog = false)
    {
        $encryptKey = mt_rand(1000000000000000, 9999999999999999);
        $header = array_merge($header, [
            'encrypt-key' => Encrypt::rsaPubEncrypt($encryptKey, $pubKey),
        ]);
        $args = Encrypt::aesEcbEncrypt(json_encode($args, JSON_UNESCAPED_UNICODE), $encryptKey);
        $body = [
            'ua' => $ua,
            'args' => $args,
            'sign' => Encrypt::getSignByUa($ua, $encryptKey, $args, $method),
            'timestamp' => Time::time(),
        ];
        $url = $url . '/' . $method;
        $ret = WebClientV2::post($url, $body, $header, $options, $ignoreLog);
        if (is_string($ret['response'])) {
            $decrypResponse = Encrypt::aesEcbDecrypt($ret['response'], $encryptKey);
            if (!empty($ret['response']) && empty($decrypResponse)) {
                throw new \yii\base\Exception('响应数据解密失败');
            }
            $ret['response'] = $decrypResponse;
        }
        return $ret;
    }
}