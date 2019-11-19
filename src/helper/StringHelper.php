<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/23
 * Time: 11:50 AM
 */

namespace xyf\lib\helper;


/**
 * 字符串工具类
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
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
}