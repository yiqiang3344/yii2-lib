<?php

namespace yiqiang3344\yii2_lib\helper;


/**
 * 阿里云日志服务SDK
 * User: xinfei
 * Date: 2021/6/16
 */
class AliLogSdk
{
    protected $endpoint;
    protected $accessKeyId;
    protected $accessKey;

    private static $_instance = null;

    private function __construct($endpoint, $accessKeyId, $accessKey)
    {
        require_once __DIR__ . '/../components/aliyunLogSdk/Log_Autoload.php';
        $this->endpoint = $endpoint;
        $this->accessKeyId = $accessKeyId;
        $this->accessKey = $accessKey;
    }

    public static function instance($endpoint, $accessKeyId, $accessKey)
    {
        if (self::$_instance === null) {
            self::$_instance = new self($endpoint, $accessKeyId, $accessKey);
        }
        return self::$_instance;
    }

    /**
     * 查询日志
     * @param string $project
     * @param string $logstore
     * @param int $startTime
     * @param int $endTime
     * @param string $topic
     * @param string $querySql
     * @param int $limit
     * @param int $offset
     * @param bool $reverse
     * @return array
     * @throws \Aliyun_Log_Exception
     */
    public function query($project, $logstore, $startTime, $endTime, $topic = '', $querySql = '', $limit = 100, $offset = 0, $reverse = false)
    {
        $client = new \Aliyun_Log_Client($this->endpoint, $this->accessKeyId, $this->accessKey);

        $request = new \Aliyun_Log_Models_GetLogsRequest(
            $project,
            $logstore,
            $startTime,
            $endTime,
            $topic,
            $querySql,
            $limit,
            $offset,
            $reverse
        );

        $ret = [];
        $response = $client->getLogs($request);
        foreach ($response->getLogs() as $log) {
            $ret[] = $log->getContents();
        }
        return $ret;
    }
}