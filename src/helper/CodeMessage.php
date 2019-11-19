<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/11
 * Time: 11:33 AM
 */

namespace xyf\lib\helper;


use yii\base\Model;
use yii\web\Response;

/**
 * 响应结果处理类
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.6
 */
class CodeMessage extends Model
{
    public static $codeMap = [
        '1' => 'success',
        '-1' => 'failed',
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
            'response' => $data,
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
            'response' => $data,
        ];
        return $response;
    }
}
