<?php

namespace yiqiang3344\yii2_lib\helper;

use yii\base\Model;

/**
 * 序列号工具类
 * User: xinfei
 * Date: 2021/1/27
 */
class Sequence extends Model
{
    public static function getId()
    {
        list($m, $t) = explode(' ', microtime());
        return date('YmdHis', $t) . floor($m * 10000) . mt_rand(100000, 999999);
    }
}