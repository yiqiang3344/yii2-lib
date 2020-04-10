<?php
/**
 * Created by PhpStorm.
 * User: sidney
 * Date: 2020/4/8
 * Time: 12:36 PM
 */

namespace xyf\lib\sms;


use yii\base\Exception;

class Template
{
    public function check($data)
    {
        throw new Exception('模板不存在');
    }
}