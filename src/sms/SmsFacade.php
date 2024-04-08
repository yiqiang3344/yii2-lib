<?php

namespace yiqiang3344\yii2_lib\sms;


use yiqiang3344\yii2_lib\helper\config\Config;
use yiqiang3344\yii2_lib\helper\Time;
use yiqiang3344\yii2_lib\helper\validator\Validator;
use yii\base\Exception;

/**
 *
 * User: sidney
 * Date: 2020/4/10
 */
class SmsFacade
{
    /**
     * @return SmsInterface
     * @throws \yii\base\Exception
     */
    protected static function getSms()
    {
        $class = Config::getString('commonSms.class', '\\xyf\\lib\\sms\\Sms');
        $sms = new $class;
        if (!$sms instanceof SmsInterface) {
            throw new Exception('must instanceof yiqiang3344\yii2_lib\sms\SmsInterface');
        }
        return $sms;
    }

    /**
     * 获取发送进程数
     * @return mixed
     * @throws \yii\base\Exception
     */
    public static function getSendProcessNum()
    {
        $smsObj = static::getSms();
        $redis = $smsObj->getRedis();
        $avgTimeSetKey = $smsObj->generateKey(Sms::REDIS_KEY_SEND_AVG_TIME_SET);
        $currentProcessNumKey = $smsObj->generateKey(Sms::REDIS_KEY_SEND_PROCESS_CURRENT_NUM);
        //移除1分钟前的
        $redis->zremrangebyscore($avgTimeSetKey, 0, bcsub(Time::getFloatMicroTime(), 60, 3));
        //计算平均耗时
        $list = $redis->zrange($avgTimeSetKey, 0, -1, 'WITHSCORES');
        $arr = [];
        foreach ($list as $k => $v) {
            if ($k % 2 != 0) {
                continue;
            }
            $arr[] = $v * 1000;
        }
        $avg = $arr ? round(array_sum($arr) / count($arr)) : 0; //默认1000毫秒
        $_currentProcessNum = $redis->get($currentProcessNumKey);
        $maxProcessNum = $smsObj->getSendMaxProcessNum(); //最大进程数限制
        $avgCfg = $smsObj->getSendExpectAvgTime(); //期望平均耗时
        $diff = $avg - $avgCfg;
        $_interval = 0;
        if ($diff > 0 && abs($diff) > 3000) {
            $_interval = 3;
        } elseif ($diff > 0 && abs($diff) > 1000) {
            $_interval = 2;
        } elseif ($diff > 0 && abs($diff) > 500) {
            $_interval = 1;
        } elseif ($diff > 0 && abs($diff) > 0) {
            $_interval = 0;
        } elseif ($diff < 0 && abs($diff) > 500) {
            $_interval = -1;
        } elseif ($diff < 0 && abs($diff) > 0) {
            $_interval = 0;
        }
        $currentProcessNum = max(1, min($_currentProcessNum + $_interval, $maxProcessNum));
        if ($currentProcessNum != $_currentProcessNum) {
            $redis->setex($currentProcessNumKey, 3600, $currentProcessNum);
            $smsObj->log('短信发送进程数调整:调整数[' . $_interval . '] 平均耗时[' . $avg . '] 期望平均耗时[' . $avgCfg . '] 差值[' . $diff . '] 当前进程数[' . $_currentProcessNum . '] 调整后进程数[' . $currentProcessNum . ']', __FUNCTION__);
        }
        return $currentProcessNum;
    }

    /**
     * 获取同步状态进程数
     * @return mixed
     * @throws \yii\base\Exception
     */
    public static function getSyncProcessNum()
    {
        $smsObj = static::getSms();
        $redis = $smsObj->getRedis();
        $avgTimeSetKey = $smsObj->generateKey(Sms::REDIS_KEY_SYNC_AVG_TIME_SET);
        $currentProcessNumKey = $smsObj->generateKey(Sms::REDIS_KEY_SYNC_PROCESS_CURRENT_NUM);
        //移除1分钟前的
        $redis->zremrangebyscore($avgTimeSetKey, 0, bcsub(Time::getFloatMicroTime(), 60, 3));
        //计算平均耗时
        $list = $redis->zrange($avgTimeSetKey, 0, -1, 'WITHSCORES');
        $arr = [];
        foreach ($list as $k => $v) {
            if ($k % 2 != 0) {
                continue;
            }
            $arr[] = $v * 1000;
        }
        $avg = $arr ? round(array_sum($arr) / count($arr)) : 0; //默认1000毫秒
        $_currentProcessNum = $redis->get($currentProcessNumKey) ?: 0;
        $maxProcessNum = $smsObj->getSyncMaxProcessNum(); //最大进程数限制
        $avgCfg = $smsObj->getSyncExpectAvgTime(); //期望平均耗时
        $diff = $avg - $avgCfg;
        $_interval = 0;
        if ($diff > 0 && abs($diff) > 3000) {
            $_interval = 3;
        } elseif ($diff > 0 && abs($diff) > 1000) {
            $_interval = 2;
        } elseif ($diff > 0 && abs($diff) > 500) {
            $_interval = 1;
        } elseif ($diff > 0 && abs($diff) > 0) {
            $_interval = 0;
        } elseif ($diff < 0 && abs($diff) > 500) {
            $_interval = -1;
        } elseif ($diff < 0 && abs($diff) > 0) {
            $_interval = 0;
        }
        $currentProcessNum = max(1, min($_currentProcessNum + $_interval, $maxProcessNum));
        if ($currentProcessNum != $_currentProcessNum) {
            $redis->setex($currentProcessNumKey, 3600, $currentProcessNum);
            $smsObj->log('短信发送状态同步进程数调整:调整数[' . $_interval . '] 平均耗时[' . $avg . '] 期望平均耗时[' . $avgCfg . '] 差值[' . $diff . '] 当前进程数[' . $_currentProcessNum . '] 调整后进程数[' . $currentProcessNum . ']', __FUNCTION__);
        }
        return $currentProcessNum;
    }

    /**
     * 短信发送统一入口
     * @throws Exception
     */
    public static function send()
    {
        $smsObj = static::getSms();
        $key = $smsObj->generateKey(Sms::REDIS_KEY_SEND_QUEUE);
        //不能使用单例，因为子程序结束时连接会回收，可能导致其他再使用此链接的子进程异常。
        try {
            //轮询redis
            $ret = $smsObj->getRedis()->rpop($key);
            $data = json_decode($ret, true);
            if (!$ret || !is_array($data)) {
                return;
            }

            Validator::checkParams($data, [
                'mobile' => ['name' => '手机号', 'type' => 'string'],
                'template_id' => ['name' => '模板ID', 'type' => 'string'],
                'subject' => ['name' => '主体', 'type' => 'string'],
                'data' => ['name' => '参数', 'type' => 'array', 'default' => []],
                't_created' => ['name' => '创建时间戳', 'type' => 'number'],
                'created_time' => ['name' => '创建时间', 'type' => 'string'],
            ]);

            $smsObj->calSendAvgTime($data['t_created']);

            $smsObj->send($data);
        } catch (\Throwable $e) {
            $smsObj->log('短信发送异常:' . $smsObj->exceptionInfo($e), 'common-sms-error');
        } finally {
            $smsObj->getRedis()->close();
        }

        return;
    }

    /**
     * 短信发送状态同步统一入口
     * @throws Exception
     */
    public static function syncStatus()
    {
        $smsObj = static::getSms();
        $key = $smsObj->generateKey(Sms::REDIS_KEY_SYNC_QUEUE);
        //不能使用单例，因为子程序结束时连接会回收，可能导致其他再使用此链接的子进程异常。
        try {
            //轮询redis
            $ret = $smsObj->getRedis()->rpop($key);
            $data = json_decode($ret, true);
            if (!$ret || !is_array($data)) {
                return;
            }
            Validator::checkParams($data, [
                'record_id' => ['name' => '发送记录ID', 'type' => 'integer'],
                'mobile' => ['name' => '手机号', 'type' => 'string'],
                'template_id' => ['name' => '模板ID', 'type' => 'string'],
                'subject' => ['name' => '主体', 'type' => 'string'],
                'channel' => ['name' => '短信通道', 'type' => 'string'],
                't_created' => ['name' => '创建时间戳', 'type' => 'number'],
                'created_time' => ['name' => '创建时间', 'type' => 'string'],
                't_send' => ['name' => '发送时间戳', 'type' => 'number'],
                't_next_sync' => ['name' => '下次同步时间戳', 'type' => 'integer'],
                'sync_num' => ['name' => '已同步次数', 'type' => 'integer', 'default' => 0],
            ]);

            //第一次最后一次同步时间等于创建时间
            $data['t_last_sync'] = $data['t_last_sync'] ?? $data['t_created'];
            $smsObj->calsyncAvgTime($data['t_last_sync']);

            $smsObj->syncStatus($data);
        } catch (\Throwable $e) {
            $smsObj->log('短信发送状态同步异常:' . $smsObj->exceptionInfo($e), 'common-sms-error');
        } finally {
            $smsObj->getRedis()->close();
        }

        return;
    }
}