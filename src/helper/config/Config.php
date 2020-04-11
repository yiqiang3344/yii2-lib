<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/11
 * Time: 11:33 AM
 */

namespace yiqiang3344\yii2_lib\helper\config;


use yii\base\Model;

/**
 * 配置文件类
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
 */
class Config extends Model
{
    /** @var $engine ConfigEngine */
    private static $engine = null;

    /**
     * @param $name
     * @param null $default
     * @return mixed
     * @throws \yii\base\Exception
     */
    public static function get($name, $default = null)
    {
        return static::getEngine()->get($name, $default);
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null|string
     * @throws \yii\base\Exception
     */
    public static function getString($name, $default = null)
    {
        return static::getEngine()->getString($name, $default);
    }

    /**
     * @param $name
     * @param null $default
     * @return bool
     * @throws \yii\base\Exception
     */
    public static function getBool($name, $default = null)
    {
        return static::getEngine()->getBool($name, $default);
    }

    /**
     * @param $name
     * @param null $default
     * @return int|null
     * @throws \yii\base\Exception
     */
    public static function getInt($name, $default = null)
    {
        return static::getEngine()->getInt($name, $default);
    }

    /**
     * @param $name
     * @param null $default
     * @return float|null
     * @throws \yii\base\Exception
     */
    public static function getFloat($name, $default = null)
    {
        return static::getEngine()->getFloat($name, $default);
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     * @throws \yii\base\Exception
     */
    public static function getArray($name, $default = null)
    {
        return static::getEngine()->getArray($name, $default);
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null|string
     * @throws \yii\base\Exception
     */
    public static function getClass($name, $default = null)
    {
        return static::getEngine()->getClass($name, $default);
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     * @throws \yii\base\Exception
     */
    public static function getCallable($name, $default = null)
    {
        return static::getEngine()->getCallable($name, $default);
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     */
    public static function getAll()
    {
        return static::getEngine()->getAll();
    }

    /**
     * @param $name
     * @param $value
     * @throws \yii\base\Exception
     */
    public static function set($name, $value)
    {
        static::getEngine()->set($name, $value);
    }

    /**
     * @param $name
     * @return bool
     * @throws \yii\base\Exception
     */
    public static function has($name)
    {
        return static::getEngine()->has($name);
    }

    /**
     * @param $name
     * @throws \yii\base\Exception
     */
    public static function remove($name)
    {
        static::getEngine()->remove($name);
    }

    /**
     * @return ConfigEngine
     * @throws \yii\base\Exception
     */
    public static function getEngine()
    {
        if (self::$engine) {
            return self::$engine;
        }
        self::$engine = ConfigEngine::instance();
        self::$engine->import(\Yii::$app->params);
        return self::$engine;
    }
}