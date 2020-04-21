<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/3/27
 * Time: 4:00 PM
 */

namespace yiqiang3344\yii2_lib\helper;


use yii\db\ActiveQuery;

trait TSoftDelete
{
    /**
     * @return ActiveQuery
     */
    public static function find()
    {
        return parent::find()->andWhere('delete_at=0');
    }

    public function delete()
    {
        $this->delete_at = time();
        return $this->update();
    }

    public function restore()
    {
        $this->delete_at = 0;
        return $this->update();
    }

    public function forceDelete()
    {
        return parent::delete();
    }
}