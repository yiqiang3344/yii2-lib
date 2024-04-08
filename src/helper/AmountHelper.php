<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/23
 * Time: 11:50 AM
 */

namespace yiqiang3344\yii2_lib\helper;


use yii\base\Model;

/**
 * 金额工具类
 * User: sidney
 * Date: 2019/8/29
 */
class AmountHelper extends Model
{
    /**
     * 获取标准格式化金额，单位元，两位小数点
     * @param int $amount 单位分
     * @return string
     */
    public static function format($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    /**
     * 数字转金额
     * @param int $number
     * @param string $intUnit
     * @param bool $isRound
     * @param bool $isExtraZero
     * @return null|string|string[]
     */
    public static function num2rmb($number = 0, $intUnit = '圆', $isRound = true, $isExtraZero = false)
    {
        // 将数字切分成两段
        if ($number == 0) {
            return '零';
        }

        $parts = explode('.', $number, 2);
        $int = isset($parts[0]) ? strval($parts[0]) : '0';
        $dec = isset($parts[1]) ? strval($parts[1]) : '';

        // 如果小数点后多于2位，不四舍五入就直接截，否则就处理
        $decLen = strlen($dec);
        if (isset($parts[1]) && $decLen > 2) {
            $dec = $isRound ? substr(strrchr(strval(round(floatval("0." . $dec), 2)), '.'), 1) : substr($parts[1], 0, 2);
        }

        // 当number为0.001时，小数点后的金额为0元
        if (empty($int) && empty($dec)) {
            return '零';
        }

        // 定义
        $chs = ['0', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖'];
        $uni = ['', '拾', '佰', '仟'];
        $decUni = ['角', '分'];
        $exp = ['', '万'];
        $res = '';

        for ($i = strlen($int) - 1, $k = 0; $i >= 0; $k++) {
            $str = '';
            for ($j = 0; $j < 4 && $i >= 0; $j++, $i--) {
                $u = $int{$i} > 0 ? $uni[$j] : '';
                $str = $chs[$int{$i}] . $u . $str;
            }

            $str = rtrim($str, '0');
            $str = preg_replace("/0+/", "零", $str);
            if (!isset($exp[$k])) {
                $exp[$k] = $exp[$k - 2] . '亿';
            }
            $u2 = $str != '' ? $exp[$k] : '';
            $res = $str . $u2 . $res;
        }

        $dec = rtrim($dec, '0');

        if (!empty($dec)) {
            $res .= $intUnit;

            if ($isExtraZero) {
                if (substr($int, -1) === '0') {
                    $res .= '零';
                }
            }

            for ($i = 0, $cnt = strlen($dec); $i < $cnt; $i++) {
                $u = $dec{$i} > 0 ? $decUni[$i] : '';
                $res .= $chs[$dec{$i}] . $u;
            }
            $res = rtrim($res, '0');
            $res = preg_replace("/0+/", "零", $res);
        } else {
            $res .= $intUnit . '整';
        }
        return $res;
    }
}