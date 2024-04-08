<?php

namespace yiqiang3344\yii2_lib\helper\exception;


use Throwable;

/**
 * 自定义的用户异常
 */
class UserException extends \yii\base\UserException
{
    public function __construct($message = "", int $code = -1, Throwable $previous = null)
    {
        $message = is_array($message) ? json_encode($message) : $message;
        parent::__construct($message, $code, $previous);
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }
}