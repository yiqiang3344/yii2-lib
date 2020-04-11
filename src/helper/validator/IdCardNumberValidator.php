<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/21
 * Time: 10:19 AM
 */

namespace yiqiang3344\yii2_lib\helper\validator;

/**
 * 身份证校验类
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
 */
class IdCardNumberValidator extends Validator
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = \Yii::t('yii', '{attribute} is not a valid id_card_number.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
        if (!is_string($value)) {
            $valid = false;
        } elseif (strlen($value) == 18) {
            $valid = preg_match('/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}([0-9]|X|x)$/', $idNumber);
        } elseif (strlen($value == 15)) {
            $valid = preg_match('/^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$/', $idNumber);
        }

        return $valid ? null : [$this->message, []];
    }
}