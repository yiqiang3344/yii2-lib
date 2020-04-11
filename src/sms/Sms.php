<?php

namespace xyf\lib\sms;


use xyf\lib\helper\redis\Redis;
use xyf\lib\helper\Time;
use yii\base\Exception;
use yii\redis\Connection;

/**
 *
 * User: sidney
 * Date: 2020/4/10
 * @since 1.0.19
 */
class Sms implements SmsInterface
{
    const REDIS_KEY_SEND_QUEUE = 'sendQueue'; //发送队列
    const REDIS_KEY_SYNC_QUEUE = 'syncQueue'; //同步状态队列
    const REDIS_KEY_SEND_PROCESS_CURRENT_NUM = 'sendProcessCurrentNum'; //发送进程当前数量
    const REDIS_KEY_SYNC_PROCESS_CURRENT_NUM = 'syncProcessCurrentNum'; //发送进程当前数量
    const REDIS_KEY_SEND_AVG_TIME_SET = 'sendAvgTimeSet'; //发送耗时有序集合
    const REDIS_KEY_SYNC_AVG_TIME_SET = 'syncAvgTimeSet'; //同步耗时有序集合

    /** @var Connection */
    protected $_redis;

    /**
     * @return Connection
     */
    public function getRedis()
    {
        if (!$this->_redis) {
            $redis = Redis::instance();
            $this->_redis = new Connection([
                'hostname' => $redis->hostname,
                'port' => $redis->port,
                'database' => $redis->database,
            ]);
        }
        return $this->_redis;
    }

    /**
     * 发送短信
     * @param $data
     * @throws Exception
     */
    public function send($data)
    {
        $this->beforeSend($data);

        //匹配短信通道
        $channel = $this->matchChannel($data);

        //发送短信
        $data['send_time'] = date('Y-m-d H:i:s');
        $data['t_send'] = Time::getFloatMicroTime();
        $data['channel'] = $channel->getName();

        $ret = $channel->sendSms($data);
        //发送成功才加入同步队列
        if ($ret) {
            $data['record_id'] = $ret['record_id'];
            $this->pushSyncQueue($data);
        }

        $this->afterSend($data, $ret);
    }

    /**
     * 同步短信发送状态
     * @param $data
     */
    public function syncStatus($data)
    {
        $data['t_last_sync'] = Time::getFloatMicroTime();//最后一次同步时间

        //同步频率判断 t=5*2^(2n) (n<7)
        if ($data['sync_num'] > 7) {
            //通过次数超过7次，状态直接设置为超时
            $this->updateStatusOvertime($data);
            return;
        } elseif (time() < $data['t_next_sync']) {
            $key = $this->generateKey(Sms::REDIS_KEY_SYNC_QUEUE);
            $this->getRedis()->lpush($key, json_encode($data, JSON_UNESCAPED_UNICODE));
            return;
        }

        $ret = $this->getChannel($data['channel'])->syncStatus($data);
        //如果没有同步结果，则需要继续同步
        if (!$ret) {
            $this->pushSyncQueue($data);
        }

        return;
    }

    public function pushSyncQueue($data)
    {
        $data['sync_num'] = ($data['sync_num'] ?? -1) + 1; //同步次数
        $data['t_next_sync'] = round($data['t_created']) + 5 * pow(2, 2 * $data['sync_num']);
        $key = $this->generateKey(Sms::REDIS_KEY_SYNC_QUEUE);
        $this->getRedis()->lpush($key, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 计算发送平均耗时
     * @param $time
     */
    public function calSendAvgTime($time)
    {
        $avgTimeSetKey = $this->generateKey(Sms::REDIS_KEY_SEND_AVG_TIME_SET);
        $this->getRedis()->zadd($avgTimeSetKey, $time, bcsub(Time::getFloatMicroTime(), $time, 3));
    }

    /**
     * 计算同步平均耗时
     * @param $time
     */
    public function calSyncAvgTime($time)
    {
        $avgTimeSetKey = $this->generateKey(Sms::REDIS_KEY_SYNC_AVG_TIME_SET);
        $this->getRedis()->zadd($avgTimeSetKey, $time, bcsub(Time::getFloatMicroTime(), $time, 3));
    }

    /**
     * 获取固定格式异常信息
     * @param \Throwable $e
     * @return string
     */
    public function exceptionInfo(\Throwable $e)
    {
        return $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }

    #############################################
    #############以下是需要重写的方法##############
    #############################################

    /**
     * @param $data
     * @throws Exception
     */
    public function beforeSend($data)
    {
        //限制检查
        $this->limitCheck($data);

        //模板检查
        $this->templateCheck($data);
    }

    /**
     * @param $data
     */
    public function AfterSend($data, $ret)
    {
    }

    /**
     * 限制检查
     * @param $data
     * @throws Exception
     */
    public function limitCheck($data)
    {
        (new Limit())->check($data);
    }

    /**
     * 模板检查
     * @param $data
     * @throws Exception
     */
    public function templateCheck($data)
    {
        (new Template())->check($data);
    }

    /**
     * 匹配短信通道
     * @param $data
     * @return \xyf\lib\sms\AChannel
     */
    public function matchChannel($data)
    {
        return (new Channel())->match($data);
    }

    /**
     * 获取短信通道
     * @param $channel
     * @return \xyf\lib\sms\AChannel
     */
    public function getChannel($channel)
    {
        return (new Channel())->getChannel($channel);
    }

    /**
     * 更新状态为超时
     * @param $data
     */
    public function updateStatusOvertime($data)
    {
    }

    /**
     * 记录日志
     * @param $message
     * @param $tag
     */
    public function log($message, $tag)
    {
        echo $message . '----' . $tag . PHP_EOL;
    }

    /**
     * 生成redis键
     * @param $key
     * @param array $params
     * @return string
     */
    public function generateKey($key, $params = [])
    {
        return 'sms:' . $key . ($params ? ':' . implode(':', $params) : '');
    }

    /**
     * 发送进程最大数量
     * @return int
     */
    public function getSendMaxProcessNum()
    {
        return 15;
    }

    /**
     * 期望发送平均耗时
     * @return int
     */
    public function getSendExpectAvgTime()
    {
        return 1000;
    }

    /**
     * 同步进程最大数量
     * @return int
     */
    public function getSyncMaxProcessNum()
    {
        return 10;
    }

    /**
     * 期望同步平均耗时
     * @return int
     */
    public function getSyncExpectAvgTime()
    {
        return 1000;
    }
}