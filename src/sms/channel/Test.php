<?php
namespace yiqiang3344\yii2_lib\sms\channel;


use yiqiang3344\yii2_lib\sms\AChannel;

/**
 * User: sidney
 * Date: 2020/4/10
 */
class Test extends AChannel
{
    public function getName()
    {
        return 'test';
    }

    public function sendSms($data)
    {
        if (date('i') % 5 == 0) {
            sleep(5);
        }
        return mt_rand(1, 100) > 20 ? 10000 : false;
    }

    public function syncStatus($data)
    {
        if (date('i') % 5 == 0) {
            sleep(5);
        }
        return mt_rand(1, 100) > 50;
    }
}