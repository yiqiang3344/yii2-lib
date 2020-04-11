<?php
namespace yiqiang3344\yii2_lib\sms;


use yii\base\Exception;

/**
 * User: sidney
 * Date: 2020/4/10
 * @since 1.0.19
 */
class Template
{
    public function check($data)
    {
        throw new Exception('模板不存在');
    }
}