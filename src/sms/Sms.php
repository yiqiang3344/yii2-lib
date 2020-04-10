<?php
/**
 * Created by PhpStorm.
 * User: sidney
 * Date: 2020/4/8
 * Time: 12:36 PM
 */

namespace common\models\sms\common;


use xyf\lib\helper\redis\Redis;
use xyf\lib\helper\Time;
use xyf\lib\helper\validator\Validator;
use xyf\lib\sms\Channel;
use xyf\lib\sms\Limit;
use xyf\lib\sms\Template;
use yii\base\Exception;
use yii\redis\Connection;

class Sms
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
    protected function getRedis()
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
     * 获取发送进程数
     * @return mixed
     */
    public static function getSendProcessNum()
    {
        $redis = (new Sms())->getRedis();
        $avgTimeSetKey = self::generateKey(Sms::REDIS_KEY_SEND_AVG_TIME_SET);
        $currentProcessNumKey = self::generateKey(Sms::REDIS_KEY_SEND_PROCESS_CURRENT_NUM);
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
        $maxProcessNum = self::getSendMaxProcessNum(); //最大进程数限制
        $avgCfg = self::getSendExpectAvgTime(); //期望平均耗时
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
            self::log('短信发送进程数调整:调整数[' . $_interval . '] 平均耗时[' . $avg . '] 期望平均耗时[' . $avgCfg . '] 差值[' . $diff . '] 当前进程数[' . $_currentProcessNum . '] 调整后进程数[' . $currentProcessNum . ']', __FUNCTION__);
        }
        return $currentProcessNum;
    }

    /**
     * 获取同步状态进程数
     * @return mixed
     */
    public static function getSyncProcessNum()
    {
        $redis = (new Sms())->getRedis();
        $avgTimeSetKey = self::generateKey(Sms::REDIS_KEY_SYNC_AVG_TIME_SET);
        $currentProcessNumKey = self::generateKey(Sms::REDIS_KEY_SYNC_PROCESS_CURRENT_NUM);
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
        $maxProcessNum = self::getSyncMaxProcessNum(); //最大进程数限制
        $avgCfg = self::getSyncExpectAvgTime(); //期望平均耗时
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
            self::log('短信发送状态同步进程数调整:调整数[' . $_interval . '] 平均耗时[' . $avg . '] 期望平均耗时[' . $avgCfg . '] 差值[' . $diff . '] 当前进程数[' . $_currentProcessNum . '] 调整后进程数[' . $currentProcessNum . ']', __FUNCTION__);
        }
        return $currentProcessNum;
    }

    /**
     * 短信发送统一入口
     */
    public static function send()
    {
        $key = self::generateKey(Sms::REDIS_KEY_SEND_QUEUE);
        //不能使用单例，因为子程序结束时连接会回收，可能导致其他再使用此链接的子进程异常。
        $smsObj = new Sms();
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

            $smsObj->_calSendAvgTime($data['t_created']);

            $smsObj->_send($data);
        } catch (\Throwable $e) {
            self::log('短信发送异常:' . $smsObj->exceptionInfo($e), 'common-sms-error');
        } finally {
            $smsObj->getRedis()->close();
        }

        return;
    }

    /**
     * 短信发送状态同步统一入口
     */
    public static function syncStatus()
    {
        $key = self::generateKey(Sms::REDIS_KEY_SYNC_QUEUE);
        //不能使用单例，因为子程序结束时连接会回收，可能导致其他再使用此链接的子进程异常。
        $smsObj = new Sms();
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
            $smsObj->_calsyncAvgTime($data['t_last_sync']);

            $smsObj->_syncStatus($data);
        } catch (\Throwable $e) {
            self::log('短信发送状态同步异常:' . $smsObj->exceptionInfo($e), 'common-sms-error');
        } finally {
            $smsObj->getRedis()->close();
        }

        return;
    }

    /**
     * 发送短信
     * @param $data
     * @throws Exception
     */
    private function _send($data)
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
    private function _syncStatus($data)
    {
        $data['t_last_sync'] = Time::getFloatMicroTime();//最后一次同步时间

        //同步频率判断 t=5*2^(2n) (n<7)
        if ($data['sync_num'] > 7) {
            //通过次数超过7次，状态直接设置为超时
            $this->updateStatusOvertime($data);
            return;
        } elseif (time() < $data['t_next_sync']) {
            $key = self::generateKey(Sms::REDIS_KEY_SYNC_QUEUE);
            $this->getRedis()->lpush($key, json_encode($data, JSON_UNESCAPED_UNICODE));
            return;
        }

        $ret = (new Channel())->getChannel($data['channel'])->syncStatus($data);
        //如果没有同步结果，则需要继续同步
        if (!$ret) {
            $this->pushSyncQueue($data);
        }

        return;
    }

    private function pushSyncQueue($data)
    {
        $data['sync_num'] = ($data['sync_num'] ?? -1) + 1; //同步次数
        $data['t_next_sync'] = round($data['t_created']) + 5 * pow(2, 2 * $data['sync_num']);
        $key = self::generateKey(Sms::REDIS_KEY_SYNC_QUEUE);
        $this->getRedis()->lpush($key, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 计算发送平均耗时
     * @param $time
     */
    private function _calSendAvgTime($time)
    {
        $avgTimeSetKey = self::generateKey(Sms::REDIS_KEY_SEND_AVG_TIME_SET);
        $this->getRedis()->zadd($avgTimeSetKey, $time, bcsub(Time::getFloatMicroTime(), $time, 3));
    }

    /**
     * 计算同步平均耗时
     * @param $time
     */
    private function _calSyncAvgTime($time)
    {
        $avgTimeSetKey = self::generateKey(Sms::REDIS_KEY_SYNC_AVG_TIME_SET);
        $this->getRedis()->zadd($avgTimeSetKey, $time, bcsub(Time::getFloatMicroTime(), $time, 3));
    }

    /**
     * 获取固定格式异常信息
     * @param \Throwable $e
     * @return string
     */
    private function exceptionInfo(\Throwable $e)
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
    protected function beforeSend($data)
    {
        //限制检查
        $this->limitCheck($data);

        //模板检查
        $this->templateCheck($data);
    }

    /**
     * @param $data
     */
    protected function AfterSend($data, $ret)
    {
    }

    /**
     * 限制检查
     * @param $data
     * @throws Exception
     */
    protected function limitCheck($data)
    {
        (new Limit())->check($data);
    }

    /**
     * 模板检查
     * @param $data
     * @throws Exception
     */
    protected function templateCheck($data)
    {
        (new Template())->check($data);
    }

    /**
     * 匹配短信通道
     * @param $data
     * @return \xyf\lib\sms\AChannel
     */
    protected function matchChannel($data)
    {
        return (new Channel())->match($data);
    }

    /**
     * 更新状态为超时
     * @param $data
     */
    protected function updateStatusOvertime($data)
    {
    }

    /**
     * 记录日志
     * @param $message
     * @param $tag
     */
    protected static function log($message, $tag)
    {
        echo $message . '----' . $tag . PHP_EOL;
    }

    /**
     * 生成redis键
     * @param $key
     * @param array $params
     * @return string
     */
    protected static function generateKey($key, $params = [])
    {
        return 'sms:' . $key . ($params ? ':' . implode(':', $params) : '');
    }

    /**
     * 发送进程最大数量
     * @return int
     */
    public static function getSendMaxProcessNum()
    {
        return 15;
    }

    /**
     * 期望发送平均耗时
     * @return int
     */
    public static function getSendExpectAvgTime()
    {
        return 1000;
    }

    /**
     * 同步进程最大数量
     * @return int
     */
    public static function getSyncMaxProcessNum()
    {
        return 10;
    }

    /**
     * 期望同步平均耗时
     * @return int
     */
    public static function getSyncExpectAvgTime()
    {
        return 1000;
    }
}