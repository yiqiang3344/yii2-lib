<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/14
 * Time: 2:57 PM
 */

namespace yiqiang3344\yii2_lib\helper;


/**
 * 统一时间处理类
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
 */
class Time
{
    /**
     * 获取秒级时间戳
     * @return int
     */
    public static function time()
    {
        return time();
    }

    /**
     * 获取日期
     * @param null $time
     * @return false|string
     */
    public static function now($time = null)
    {
        return date("Y-m-d H:i:s", $time ?: time());
    }

    /**
     * 获取带毫秒的日期
     * @param null $time
     * @return string
     */
    public static function nowWithMicros($time = null)
    {
        $time = $time ?: microtime();
        list($m, $t) = explode(' ', microtime());
        return date("Y-m-d H:i:s", $t) . '.' . floor($m * 10000);
    }

    /**
     * 获取毫秒时间戳
     * @return float
     */
    public static function getMicrotime()
    {
        return round(microtime(true) * 1000, 0);
    }

    /**
     * 获取指定月份后的当前日期
     * @param int $num
     * @param $date
     * @return false|string
     */
    public static function getNexMonthDay($num = 1, $date = null)
    {
        $date = $date ?: date('Y-m-d H:i:s');
        $date = substr("$date", 0, 10);
        list($y, $m, $d) = explode('-', $date);
        $m += $num;
        while ($m > 12) {
            $m -= 12;
            $y++;
        }

        $last_day = date('t', strtotime("$y-$m-1"));
        if ($d > $last_day) {
            $d = $last_day;
        }

        $nexMon = date('Y-m-d H:i:s', strtotime("$y-$m-$d 23:59:59"));
        return $nexMon;
    }

    /**
     * 获取当前日期星期几的中文名
     * @param $time
     * @return mixed
     */
    public static function getDayOfWeek($time)
    {
        $week = ['0' => '周日', '1' => '周一', '2' => '周二', '3' => '周三', '4' => '周四', '5' => '周五', '6' => '周六'];
        return $week[date('w', $time)];
    }

    /**
     * 当前时间与指定日期相差天数
     * @param string $defaultDay
     * @return float
     */
    public static function getSubDayFromToday($defaultDay = '2015-01-22')
    {
        return floor((time() - strtotime($defaultDay)) / 86400);
    }

    /**
     * 获取指定时间戳的当日的开始时间
     *
     * @return string '2018-11-29 00:00:00'
     */
    public static function getDailyStartTime($time = null)
    {
        $time = $time ?: time();

        return date('Y-m-d 00:00:00', $time);
    }

    /**
     * 获取是指定时间戳的当天的截止时间
     * @param null $time
     * @return false|string
     */
    public static function getDailyEndTime($time = null)
    {
        $time = $time ?: time();

        return date('Y-m-d 23:59:59', $time);
    }

    /**
     * 获取毫秒时间的差值
     * @param $microTime1
     * @param null $microTime2
     * @return float
     * @since 1.0.3
     */
    public static function getSubMicroTime($microTime1, $microTime2 = null)
    {
        $microTime2 = $microTime2 ?: microtime();
        list($m1, $t1) = explode(' ', $microTime1);
        list($m2, $t2) = explode(' ', $microTime2);
        $m = bcsub($m1, $m2, 4);
        $t = $t1 - $t2;
        return floatval(bcadd($t, $m, 4));
    }


    /**
     * 3位小数的浮点型时间戳
     * @return string
     */
    public static function getFloatMicroTime()
    {
        return sprintf('%0.3f', microtime(true));
    }
}