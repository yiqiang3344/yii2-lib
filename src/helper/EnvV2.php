<?php

namespace yiqiang3344\yii2_lib\helper;


use yii\base\Exception;

/**
 * 环境变量类V2版
 * User: sidney
 * Date: 2020/1/7
 */
class EnvV2 extends Env
{
    protected static $attributes = [];

    protected static function getGlobalAttributes()
    {
        return ArrayHelper::merge(self::$globalAttributes, static::$globalAttributes);
    }

    /**
     * 设置全局属性方法
     * @param $name
     * @param $value
     * @throws Exception
     */
    public static function setAttr($name, $value)
    {
        if (!isset(static::getGlobalAttributes()[$name])) {
            throw new Exception($name . '：属性不合法');
        }
        static::$attributes[$name] = $value;
    }

    public static function getAttr($name)
    {
        return static::$attributes[$name] ?? null;
    }
}