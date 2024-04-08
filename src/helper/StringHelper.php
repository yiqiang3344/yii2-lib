<?php

namespace yiqiang3344\yii2_lib\helper;


/**
 * 字符串工具类
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.61
 */
class StringHelper extends \yii\helpers\StringHelper
{
    /**
     * 遮盖
     * @param $string
     * @param $start
     * @param $length
     * @param string $mask
     * @param bool $isUtf8
     * @return mixed
     */
    public static function cover($string, $start, $length, $mask = '*', $isUtf8 = false)
    {
        $replacement = '';
        for ($i = 0; $i < $length; $i++) {
            $replacement .= $mask;
        }
        if ($isUtf8) {
            $len = mb_strlen($string, 'UTF-8');
            if ($len > 2) {
                $ret = mb_substr($string, 0, $start, 'UTF-8') . $replacement . mb_substr($string, -1, $len - $length, 'UTF-8');
            } else {
                $ret = mb_substr($string, 0, $start, 'UTF-8') . $replacement;
            }
            return $ret;
        }
        return substr_replace($string, $replacement, $start, $length);
    }

    /**
     * 用星星遮盖
     * @param $string
     * @param $start
     * @param $length
     * @return mixed
     */
    public static function coverWithStar($string, $start, $length)
    {
        return self::cover($string, $start, $length);
    }

    /**
     * 遮盖名称
     * @param $string
     * @return mixed
     */
    public static function coverName($string)
    {
        $mbLen = mb_strlen($string);
        $len = strlen($string);
        $isUtf8 = false;
        $coverLen = $len;
        if ($len != $mbLen) {
            $isUtf8 = true;
            $coverLen = $mbLen;
        }
        return self::cover($string, 1, $coverLen > 2 ? $coverLen - 2 : 1, '*', $isUtf8);
    }

    /**
     * 驼峰转横杠式
     * @param $string
     * @return string
     */
    public static function humpToBar($string)
    {
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '-' . strtolower($matches[0]);
        }, lcfirst($string));
        return $str;
    }

    /**
     * 版本号数字转点分隔
     * @param $str
     * @return string
     */
    public static function versionToPoint($str)
    {
        $str = (int)$str;
        if (empty($str)) {
            return '';
        }
        $v1 = bcdiv($str, 100, 2);
        $v1c = bcdiv($str, 100, 0);
        $v1 = bcmul(bcsub($v1, $v1c, 2), 100, 0);
        $v2 = bcdiv($v1c, 100, 2);
        $v2c = bcdiv($v1c, 100, 0);
        $v2 = bcmul(bcsub($v2, $v2c, 2), 100, 0);
        return $v2c . '.' . $v2 . '.' . $v1;
    }

    /**
     * 版本号数字转点分隔
     * @param $str
     * @return string
     */
    public static function versionToNumber($str)
    {
        $str = (string)$str;
        if (empty($str)) {
            return '';
        }
        $arr = explode('.', $str);
        return sprintf('%s%02d%02d', $arr[0], $arr[1], $arr[2]);
    }

    /**
     * 横杠转下滑线式
     * @param $string
     * @return string
     */
    public static function lineToUnder($string)
    {
        $str = str_replace('-', '_', $string);
        return $str;
    }

    /**
     * 随机字符串
     * @param $length
     * @param $onlyNum false
     */
    public static function getRandom(int $length, bool $onlyNum = false)
    {
        $s = '0123456789' . ($onlyNum ? '' : 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $last = strlen($s) - 1;
        $res = '';
        for ($i = 0; $i < $length; $i++) {
            $res .= $s[rand(0, $last)];
        }
        return $res;
    }
}