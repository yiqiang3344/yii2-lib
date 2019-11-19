<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/21
 * Time: 10:19 AM
 */

namespace xyf\lib\helper\validator;

/**
 * 中文名校验类
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
 */
class ChineseNameValidator extends Validator
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = \Yii::t('yii', '{attribute} is not a valid chinese name.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
        if (!is_string($value)) {
            $valid = false;
        } else {
            //新疆等少数民族可能有·
            $str = str_replace("·", '', $value);
            if (preg_match('/^[\x7f-\xff]+$/', $str)) {
                $valid = true;//全是中文
            } else {
                $valid = false;//不全是中文
            }
        }

        return $valid ? null : [$this->message, []];
    }
}