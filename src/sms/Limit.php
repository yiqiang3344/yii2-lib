<?php
/**
 * Created by PhpStorm.
 * User: sidney
 * Date: 2020/4/8
 * Time: 12:36 PM
 */

namespace xyf\lib\sms;


use yii\base\Exception;

class Limit
{
    public function check($data){
        throw new Exception('已超每日上限');
    }
}