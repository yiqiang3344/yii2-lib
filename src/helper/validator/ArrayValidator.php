<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/21
 * Time: 10:19 AM
 */

namespace yiqiang3344\yii2_lib\helper\validator;

/**
 * 数组校验类
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
 */
class ArrayValidator extends Validator
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = \Yii::t('yii', '{attribute} is not a valid Array.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
        $valid = true;
        if (!is_array($value)) {
            $valid = false;
        }

        return $valid ? null : [$this->message, []];
    }
}