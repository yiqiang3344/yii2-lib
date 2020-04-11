<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/11
 * Time: 11:33 AM
 */

namespace yiqiang3344\yii2_lib\helper;


use yii\db\ActiveRecord;

/**
 * 数组工具类
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
 */
class ArrayHelper extends \yii\helpers\ArrayHelper
{
    public static function listMap(array $array, $key, $columns = null)
    {
        $data = [];
        foreach ($array as $item) {
            if (is_object($item)) {
                $data[$item->$key] = $columns ? self::cp($item, $columns) : $item;
            } elseif (is_array($item)) {
                $data[$item[$key]] = $columns ? self::cp($item, $columns) : $item;
            }
        }
        return $data;
    }

    public static function cpList(array $array, array $columns)
    {
        $data = [];
        array_map(function ($row) use ($columns, &$data) {
            $data[] = ArrayHelper::cp($row, $columns);
        }, $array);
        return $data;
    }

    public static function cp($item, array $columns)
    {
        $data = [];
        $row = $item instanceof ActiveRecord ? $item->toArray() : $item;
        array_walk($row, function ($v, $k) use ($columns, &$data) {
            if (in_array($k, $columns)) {
                $data[$k] = $v;
            }
        });
        return $data;
    }
}