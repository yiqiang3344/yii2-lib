<?php

namespace yiqiang3344\yii2_lib\helper\webClient;

use yiqiang3344\yii2_lib\helper\ArrayHelper;
use yiqiang3344\yii2_lib\helper\config\Config;
use yiqiang3344\yii2_lib\helper\EnvV2;
use yiqiang3344\yii2_lib\helper\webClient\log\WebClientLog;
use yiqiang3344\yii2_lib\helper\webClient\log\WebClientTimeoutLog;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\httpclient\Request;

/**
 *
 * User: sidney
 * Date: 2020/1/7
 */
class WebClient extends Model
{
    public static $isTimeout = false;
    public static $httpStatusCode = null;
    public static $result = null;

    /**
     * 获取内部系统域名，可替换环境，默认根据qa_env环境替换，qa_env 为空时，根据当前环境替换：prod->空，其他->test
     * @param $name
     * @return mixed
     * @throws \yii\base\Exception
     */
    public static function getInnerDomain($name)
    {
        $ret = Config::getString($name);
        if (empty($ret)) {
            throw new \yii\base\Exception('域名不存在:' . $name);
        }
        $replace = EnvV2::getQaEnv() ?: (EnvV2::getEnv() == 'prod' ? '' : 'test');
        return str_replace('env_place_holder', $replace, $ret);
    }

    /**
     * GET统一请求方法
     * @param $url
     * @param $data
     * @param array $header
     * @param array $options
     * @return array
     * @throws \yii\base\Exception
     * @throws InvalidConfigException
     */
    public static function get($url, $data = [], $header = [], $options = [])
    {
        static::resetStatic();

        $_log = new WebClientLog();
        $_log->start(__FUNCTION__, $url, $data, $header, $options);

        //请求
        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport' // only cURL supports the options we need
        ]);
        $request = $client->createRequest();
        //设置header
        if ($header) {
            $request->setHeaders($header);
        }
        //设置配置项
        if ($options) {
            $request->setOptions($options);
        } else {
            $request->setOptions([
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 10,
            ]);
        }
        try {
            $request->setMethod('GET');
            $_result = static::handleSend($request, $_log, $url, $data);
        } catch (\Exception $e) {
            $_result = false;
            static::handleError($e, $_log, $url, $data, $header, $options);
        }
        //写入请求日志
        $_log->writeLog();
        return $_result;
    }


    /**
     * POST统一请求方法
     * @param $url
     * @param $data
     * @param array $header
     * @param array $options
     * @param bool $ignoreLog
     * @return array
     * @throws InvalidConfigException
     */
    public static function post($url, $data, $header = [], $options = [], $ignoreLog = false)
    {
        static::resetStatic();

        $_log = new WebClientLog();
        $_log->start(__FUNCTION__, $url, $data, $header, $options);

        //请求
        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport' // only cURL supports the options we need
        ]);
        $request = $client->createRequest();
        //设置header
        if ($header) {
            if ((isset($header['Content-Type']) && strpos(strtolower($header['Content-Type']), 'application/json') !== false)
                || (isset($header['content-type']) && strpos(strtolower($header['content-type']), 'application/json') !== false)) {
                $request->setFormat(Client::FORMAT_JSON);
                unset($header['content-type']);
                unset($header['Content-Type']);
            }
            $request->setHeaders($header);
        } else {
            $request->setHeaders([
                'content-type' => 'application/x-www-form-urlencoded'
            ]);
        }

        //设置配置项
        if ($options) {
            $request->setOptions($options);
        } else {
            $request->setOptions([
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 10,
            ]);
        }
        try {
            if (isset($data['file']) && !empty($data['file'])) {
                foreach ($data['file'] as $key => $value) {
                    $request->addFile($key, $value['path'], ['mimeType' => $value['type']]);
                }
            }
            $request->setMethod('POST');
            $_result = static::handleSend($request, $_log, $url, $data);
        } catch (\Exception $e) {
            $_result = false;
            static::handleError($e, $_log, $url, $data, $header, $options);
        }
        //写入响应日志
        !$ignoreLog && $_log->writeLog();
        return $_result;
    }

    /**
     * 风控接口统一请求方法
     * @param $url
     * @param array $body
     * @param array $head
     * @param array $header
     * @param array $options
     * @return array
     * @throws \yii\base\Exception
     * @throws InvalidConfigException
     */
    public static function postRisk($url, $data, $head, $header = [], $options = [])
    {
        static::resetStatic();

        //写入访问日志
        $_log = new WebClientLog();
        $_log->start(__FUNCTION__, $url, $data, ArrayHelper::merge($header, ['Rpc-Context' => $head]), $options);

        //请求
        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport' // only cURL supports the options we need
        ]);
        $request = $client->createRequest();
        //设置header
        $request->setHeaders([
            'Rpc-Context' => json_encode($head, JSON_UNESCAPED_UNICODE),
        ])->setFormat(Client::FORMAT_JSON);
        //设置配置项
        if ($options) {
            $request->setOptions($options);
        } else {
            $request->setOptions([
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 10,
            ]);
        }
        try {
            $request->setMethod('POST');
            $_result = static::handleSend($request, $_log, $url, $data);
        } catch (\Exception $e) {
            $_result = false;
            static::handleError($e, $_log, $url, $data, $header, $options);
        }
        //写入响应日志
        $_log->writeLog();
        return $_result;
    }

    /**
     * PUT统一请求方法
     * @param $url
     * @param string $data
     * @param array $header
     * @param array $options
     * @return array
     */
    public static function put($url, $data, $header = [], $options = [])
    {
        static::resetStatic();

        $_log = new WebClientLog();
        $shortData = !empty($options['base64_encode_switch']) ? base64_encode($data) : md5(base64_encode($data));
        $_log->start(__FUNCTION__, $url, $shortData, $header, $options);

        try {
            $_header = [];
            foreach ($header as $name => $value) {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                $_header[] = "$name: $value";
            }
            $outputHeaders = [];
            $curl = curl_init();
            if (isset($options[CURLOPT_CONNECTTIMEOUT])) {
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $options[CURLOPT_CONNECTTIMEOUT]);
            } else {
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
            }
            if (isset($options[CURLOPT_TIMEOUT])) {
                curl_setopt($curl, CURLOPT_TIMEOUT, $options[CURLOPT_TIMEOUT]);
            } else {
                curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            }
            curl_setopt($curl, CURLOPT_URL, $url); //设置请求的URL
            curl_setopt($curl, CURLOPT_HTTPHEADER, $_header);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //设置获取响应header方法
            curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($resource, $headerString) use (&$outputHeaders) {
                $header = trim($headerString, "\n\r");
                if (strlen($header) > 0) {
                    $outputHeaders[] = $header;
                }
                return mb_strlen($headerString, '8bit');
            });
            $output = curl_exec($curl);
            $errorNumber = curl_errno($curl);
            $errorMessage = curl_error($curl);
            curl_close($curl);
            if ($errorNumber > 0) {
                throw new Exception('Curl error: #' . $errorNumber . ' - ' . $errorMessage, $errorNumber);
            }

            //获取http status code
            $httpCode = null;
            foreach ($outputHeaders as $name => $value) {
                if (!is_int($name)) {
                    continue;
                }
                if (strpos($value, 'HTTP/') === 0) {
                    $parts = explode(' ', $value, 3);
                    $httpCode = $parts[1];
                }
            }
            $ret = false;
            if (strncmp('20', $httpCode, 2) === 0) {
                $ret = json_decode($output, true);
            }
            $_log->setResult($output, $httpCode);
            static::$httpStatusCode = $httpCode;
            static::$result = $output;
        } catch (\Exception $e) {
            $ret = false;
            static::handleError($e, $_log, $url, $data, $header, $options);
        }

        //写入请求日志
        $_log->writeLog();
        return $ret;
    }

    /**
     * DELETE统一请求方法
     * @param $url
     * @param $data
     * @param array $header
     * @param array $options
     * @return array
     * @throws \yii\base\Exception
     * @throws InvalidConfigException
     */
    public static function delete($url, $data = [], $header = [], $options = [])
    {
        static::resetStatic();

        $_log = new WebClientLog();
        $_log->start(__FUNCTION__, $url, $data, $header, $options);

        //请求
        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport' // only cURL supports the options we need
        ]);
        $request = $client->createRequest();
        //设置header
        if ($header) {
            if (strtolower($header['content-type']) == 'application/json') {
                $request->setFormat(Client::FORMAT_JSON);
                unset($header['content-type']);
            }
            $request->setHeaders($header);
        } else {
            $request->setHeaders([
                'content-type' => 'application/x-www-form-urlencoded'
            ]);
        }
        //设置配置项
        if ($options) {
            $request->setOptions($options);
        } else {
            $request->setOptions([
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 10,
            ]);
        }
        try {
            $request->setMethod('DELETE');
            $_result = static::handleSend($request, $_log, $url, $data);
        } catch (\Exception $e) {
            $_result = false;
            static::handleError($e, $_log, $url, $data, $header, $options);
        }
        //写入请求日志
        $_log->writeLog();
        return $_result;
    }

    /**
     * 重置静态变量
     */
    public static function resetStatic()
    {
        static::$isTimeout = false;
        static::$httpStatusCode = null;
        static::$result = null;
    }

    /**
     * @param Exception $e
     * @param WebClientLog $log
     * @param $url
     * @param $data
     * @param $header
     * @param $options
     */
    public static function handleError(\Exception $e, WebClientLog $log, $url, $data, $header, $options)
    {
        static::$httpStatusCode = $e->getCode();
        static::$result = $e->getMessage();
        $log->setResult(static::$result, $e->getCode());
        if (strpos(static::$result, 'Operation timed out after') !== false) {
            static::$isTimeout = true;
            //超时日志统计
            WebClientTimeoutLog::log($url, $data, $header, $options, static::$result);
        }
    }

    /**
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
        if ($response->isOk) {
            $_result = $result = $response->getData();
        } else {
            $result = '[' . $response->getStatusCode() . '] ' . $response->getContent();
            $_result = false;
        }
        $log->setResult($result, $response->getStatusCode());
        static::$httpStatusCode = $response->getStatusCode();
        static::$result = $response->getContent();
        return $_result;
    }
}