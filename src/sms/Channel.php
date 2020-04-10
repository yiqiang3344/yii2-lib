<?php
/**
 * Created by PhpStorm.
 * User: sidney
 * Date: 2020/4/8
 * Time: 12:36 PM
 */

namespace xyf\lib\sms;


use xyf\lib\sms\channel\Test;

class Channel
{
    /**
     * 匹配通道
     * @param $data
     * @return AChannel
     */
    public function match($data)
    {
        return new Test();
    }

    /**
     * 根据通道标识获取通道实例
     * @param $channel
     * @return AChannel
     */
    public function getChannel($channel)
    {
        $className = '\\xyf\\lib\\sms\\channel\\' . $channel;
        return new $className;
    }
}