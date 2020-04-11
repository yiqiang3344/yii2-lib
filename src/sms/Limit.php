<?php
namespace xyf\lib\sms;


use yii\base\Exception;

/**
 * User: sidney
 * Date: 2020/4/10
 * @since 1.0.19
 */
class Limit
{
    public function check($data){
        throw new Exception('已超每日上限');
    }
}