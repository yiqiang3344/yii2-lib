<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/21
 * Time: 10:19 AM
 */

namespace yiqiang3344\yii2_lib\helper\validator;

/**
 * 手机号校验类
 * User: sidney
 * Date: 2019/8/29
 */
class MobileValidator extends Validator
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = \Yii::t('yii', '{attribute} is not a valid mobile.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
        if (!is_string($value)) {
            $valid = false;
        } elseif (!preg_match('/^1[3456789]\d{9}$/i', $value, $matches)) {
            $valid = false;
        } else {
            $valid = true;
        }

        return $valid ? null : [$this->message, []];
    }
}