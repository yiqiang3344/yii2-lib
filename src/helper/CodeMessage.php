<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/11
 * Time: 11:33 AM
 */

namespace yiqiang3344\yii2_lib\helper;


use yiqiang3344\yii2_lib\helper\config\Config;
use yiqiang3344\yii2_lib\helper\exception\ErrorException;
use yiqiang3344\yii2_lib\helper\exception\OptionsException;
use yiqiang3344\yii2_lib\helper\exception\ParamsInvalidException;
use yii\base\InvalidRouteException;
use yii\base\Model;
use yii\base\UserException;
use yii\helpers\VarDumper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * 响应结果处理类
 * User: sidney
 * Date: 2019/8/29
 */
class CodeMessage extends Model
{
    public static $codeMap = [
        '1' => 'success',
        '-1' => '',
        '-2' => '',
        '-3' => '',

        #########系统异常##########
        '-20' => 'system error',
    ];

    public static function success($data = null)
    {
        $code = '1';
        $message = static::$codeMap[$code];
        $response = \Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        $response->data = [
            'status' => intval($code),
            'message' => $message,
            'response' => is_null($data) ? (new \stdClass()) : $data,
            'time' => Time::time(),
        ];
        return $response;
    }

    public static function failed($code, $subMessage = '', $data = null)
    {
        $message = 'failed';
        $mainMessage = static::$codeMap[$code];
        //如果是数组，则代表是需要匹配替换参数
        if (is_array($subMessage)) {
            $message = str_replace(array_keys($subMessage), array_values($subMessage), $mainMessage);
        } else {
            if ($mainMessage && $subMessage) {
                $message = $mainMessage . ':' . $subMessage;
            } elseif (empty($subMessage) && $mainMessage) {
                $message = $mainMessage;
            } elseif (empty($mainMessage) && $subMessage) {
                $message = $subMessage;
            }
        }
        $response = \Yii::$app->getResponse();
        $response->format = Response::FORMAT_JSON;
        $response->data = [
            'status' => intval($code),
            'message' => $message,
            'response' => is_null($data) ? (new \stdClass()) : $data,
            'time' => Time::time(),
        ];
        return $response;
    }

    /**
     * @param $exception
     * @param bool $showException
     * @return \yii\console\Response|\yii\web\Response
     */
    public static function getResponseFromException($exception, $showException = false)
    {
        $httpStatusSwitch = Config::getBool('switch.response.httpStatus', false);
        if ($exception instanceof OptionsException) { //option请求正常响应
            $response = static::success();
            $response->setStatusCode(200);
        } elseif ($exception instanceof ParamsInvalidException) { //参数异常
            $response = static::failed($exception->getCode() ?: -1, json_decode($exception->getMessage(), true) ?? $exception->getMessage());
            $httpStatusSwitch && $response->setStatusCode(400);
        } elseif ($exception instanceof ErrorException) { //系统异常
            $response = static::failed($exception->getCode() ?: -1, json_decode($exception->getMessage(), true) ?? $exception->getMessage());
            $httpStatusSwitch && $response->setStatusCode(500);
        } elseif ($exception instanceof InvalidRouteException || $exception instanceof NotFoundHttpException) { //路由不存在
            $response = static::failed($exception->getCode() ?: -1, 'Not Found');
            $httpStatusSwitch && $response->setStatusCode(404);
        } elseif ($exception instanceof UserException) { //其他用户操作问题，属于业务逻辑异常
            $response = static::failed($exception->getCode() ?: -1, json_decode($exception->getMessage(), true) ?? $exception->getMessage());
            $httpStatusSwitch && $response->setStatusCode(608);
        } elseif (YII_DEBUG && $showException) { //debug模式，打印错误
            self::showExceptionMessage($exception);
            exit(1);
        } else { //其他都属于系统异常
            $response = static::failed(-20);
            $httpStatusSwitch && $response->setStatusCode(500);
        }
        return $response;
    }

    public static function showExceptionMessage($exception)
    {
        $msg = "exception:\n";
        $msg .= (string)$exception;
        if (YII_DEBUG) {
            if (PHP_SAPI === 'cli') {
                echo $msg . "\n";
            } else {
                echo '<pre>' . htmlspecialchars($msg, ENT_QUOTES, \Yii::$app->charset) . '</pre>';
            }
        } else {
            echo 'An internal server error occurred.';
        }
        $msg .= "\n\$_SERVER = " . VarDumper::export($_SERVER);
        error_log($msg);
        if (defined('HHVM_VERSION')) {
            flush();
        }
        \Yii::getLogger()->flush(true);
        exit(1);
    }
}