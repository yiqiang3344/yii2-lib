<?php

namespace yiqiang3344\yii2_lib\commonSms;

use yiqiang3344\yii2_lib\helper\config\Config;
use yiqiang3344\yii2_lib\helper\encrypt\Encrypt;
use yiqiang3344\yii2_lib\helper\EnvV2;
use yiqiang3344\yii2_lib\helper\Time;
use yiqiang3344\yii2_lib\helper\webClient\WebClient;
use yiqiang3344\yii2_lib\helper\webClient\WebClientV2;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Model;

/**
 * 短信中心SDK
 * User: sidney
 * Date: 2021/1/29
 */
class SmsCenter extends Model
{
    protected function log($message, $tag)
    {
    }

    /**
     * 消息中心统一请求方法
     * @param $method
     * @param array $args
     * @param array $header
     * @param array $options
     * @param array $config
     * @return array|bool
     * @throws Exception
     * @throws InvalidConfigException
     */
    protected static function requestSmsCenter($method, $args, $header = [], $options = [], $config = [])
    {
        $header = array_merge([
            'content-type' => 'application/json',
            'request-float-number' => EnvV2::getRequestFloatNumber(),
        ], $header);
        if (!isset($options[CURLOPT_CONNECTTIMEOUT])) {
            $options[CURLOPT_CONNECTTIMEOUT] = 10;
        }
        if (!isset($options[CURLOPT_TIMEOUT])) {
            $options[CURLOPT_TIMEOUT] = 5;
        }
        $ua = $config['ua'] ?? Config::getString('secret.sms_center_ua');
        $signKey = $config['sign_key'] ?? Config::getString('secret.sms_center_sign_key');
        if (empty($config['url']) && !WebClient::getInnerDomain('domain.inner_sms_center')) {
            return false;
        }
        $url = $config['url'] ?? EnvV2::getProtocol(false, true) . WebClient::getInnerDomain('domain.inner_sms_center');
        $body = [
            'ua' => $ua,
            'args' => $args,
            'sign' => Encrypt::getSignByUa($ua, $signKey, $args, $method),
            'timestamp' => Time::time(),
        ];
        $url = $url . '/' . $method;
        $ret = WebClientV2::post($url, $body, $header, $options);
        if (!isset($ret['status'])) {
            return false;
        }
        return $ret;
    }

    /**
     * 发送通知，包括机器人消息及邮件
     * @param $subject
     * @param $content
     * @param $addressBook
     *      [
     *          ['email'=>'','mobile'=>''],
     *      ]
     * @param array $bizTypes
     * @param array $config
     * @return bool
     */
    public function sendNotify($subject, $content, $addressBook, $bizTypes = [], $config = [])
    {
        try {
            $mobiles = array_column($addressBook, 'mobile');
            foreach ($bizTypes as $bizType) {
                static::requestSmsCenter('robot/send-msg', [
                    'biz_type' => $bizType,
                    'msg_type' => $config['msg_type'] ?? 'text',
                    'title' => $subject,
                    'content' => $content,
                    'at_mobiles' => $mobiles,
                    'at_all' => false,
                ], [], [], $config);
            }
            $emails = array_column($addressBook, 'email');
            if ($emails) {
                static::requestSmsCenter('email/send', [
                    'subject' => $subject,
                    'content' => $content,
                    'emails' => $emails,
                ], [], [], $config);
            }
        } catch (\Throwable $e) {
            $this->log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), 'sms_center_error_notify_failed');
            return false;
        }
        return true;
    }
}