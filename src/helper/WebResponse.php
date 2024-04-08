<?php

namespace yiqiang3344\yii2_lib\helper;


use yii\web\Response;

/**
 */
class WebResponse extends Response
{
    /**
     * @return bool whether this response has a valid [[statusCode]].
     */
    public function getIsInvalid()
    {
        return $this->getStatusCode() < 100 || $this->getStatusCode() >= 700;
    }
}