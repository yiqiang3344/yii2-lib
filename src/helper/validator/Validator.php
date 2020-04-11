<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/21
 * Time: 10:19 AM
 */

namespace yiqiang3344\yii2_lib\helper\validator;


use yii\base\Exception;
use yii\base\UserException;
use yii\validators\DateValidator;
use yii\validators\Validator as Base;

/**
 * 参数校验类
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
 */
class Validator extends Base
{
    /**
     * @var array list of built-in validators (name => class or configuration)
     */
    public static $builtInValidators = [
        'boolean' => 'yii\validators\BooleanValidator',
        'captcha' => 'yii\captcha\CaptchaValidator',
        'compare' => 'yii\validators\CompareValidator',
        'date' => 'yii\validators\DateValidator',
        'datetime' => [
            'class' => 'yii\validators\DateValidator',
            'type' => DateValidator::TYPE_DATETIME,
        ],
        'time' => [
            'class' => 'yii\validators\DateValidator',
            'type' => DateValidator::TYPE_TIME,
        ],
        'default' => 'yii\validators\DefaultValueValidator',
        'double' => 'yii\validators\NumberValidator',
        'each' => 'yii\validators\EachValidator',
        'email' => 'yii\validators\EmailValidator',
        'exist' => 'yii\validators\ExistValidator',
        'file' => 'yii\validators\FileValidator',
        'filter' => 'yii\validators\FilterValidator',
        'image' => 'yii\validators\ImageValidator',
        'in' => 'yii\validators\RangeValidator',
        'integer' => [
            'class' => 'yii\validators\NumberValidator',
            'integerOnly' => true,
        ],
        'match' => 'yii\validators\RegularExpressionValidator',
        'number' => 'yii\validators\NumberValidator',
        'required' => 'yii\validators\RequiredValidator',
        'safe' => 'yii\validators\SafeValidator',
        'string' => 'yii\validators\StringValidator',
        'trim' => [
            'class' => 'yii\validators\FilterValidator',
            'filter' => 'trim',
            'skipOnArray' => true,
        ],
        'unique' => 'yii\validators\UniqueValidator',
        'url' => 'yii\validators\UrlValidator',
        'ip' => 'yii\validators\IpValidator',

        //自定义
        'array' => 'common\helper\validator\ArrayValidator',
        'mobile' => 'common\helper\validator\MobileValidator',
        'id_card_number' => 'common\helper\validator\IdCardNumberValidator',
        'chinese_name' => 'common\helper\validator\ChineseNameValidator',
    ];

    /**
     * 通用参数校验方法
     * @param $params
     * @param array $needParams 要检查的参数
     *   [
     *       '字段名' => [
     *            'name' => 字段说明,
     *            'message' => 自定义错误信息,
     *            'default' => 默认值,
     *            'type' => 检查类型,
     *       ],
     *   ]
     * @return bool
     * @throws \yii\base\Exception
     * @since 1.0.8
     */
    public static function checkParams(&$params, $needParams)
    {
        $subMessage = true;
        foreach ($needParams as $key => $v) {
            if (isset($v['type']) && !isset($v['default']) && empty($params[$key])) {
                $subMessage = $v['message'] ?? ' [' . $key . '] ' . '不能为空';
                break;
            } elseif (isset($v['type'])
                && (!isset($v['default']) || isset($params[$key]))
            ) {
                if (!isset(self::$builtInValidators[$v['type']])) {
                    throw new Exception('检查类型不存在');
                }
                $validatorConfig = self::$builtInValidators[$v['type']];
                $class = is_array($validatorConfig) ? $validatorConfig['class'] : $validatorConfig;
                /** @var Validator $validator */
                $validator = new $class;
                if (!$validator->validate($params[$key] ?? null, $subMessage)) {
                    $subMessage = $v['message'] ?? ' [' . $key . '] ' . $subMessage;
                    break;
                }
            } elseif (!isset($params[$key])) {
                if (isset($v['default'])) {
                    $params[$key] = $v['default'];
                } else {
                    $params[$key] = null;
                }
            }
        }
        if ($subMessage !== true) {
            throw new UserException($subMessage, -1);
        }
        return true;
    }
}