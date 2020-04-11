<?php
namespace xyf\lib\sms\channel;


use xyf\lib\sms\AChannel;

/**
 * User: sidney
 * Date: 2020/4/10
 * @since 1.0.19
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