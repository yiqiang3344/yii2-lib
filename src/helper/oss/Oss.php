<?php

namespace yiqiang3344\yii2_lib\helper\oss;

use OSS\Core\OssException;
use OSS\OssClient;

/**
 * 通用OSS
 * User: sidney
 * Date: 2019/8/29
 */
class Oss
{
    const RETRY_NUM = 5; //超时重试次数

    protected $endpoint; //域名
    protected $bucket; //存储空间名
    /** @var OssClient */
    public $ossClient; //oss客户端对象
    protected $securityToken;//临时访问url的token
    protected $timeout; //临时访问url的有效期
    protected $domain; //临时访问url的自定义域名
    protected $config;

    public $error;

    private static $_instances = [];

    /**
     * @param $bucket
     * @param array $config
     * @return Oss
     */
    public static function getInstance($bucket, $config = [])
    {
        if (empty(self::$_instances[$bucket])) {
            self::$_instances[$bucket] = new self($bucket, $config);
        }
        return self::$_instances[$bucket];
    }

    /**
     * Oss constructor.
     * @param array $config
     */
    public function __construct($bucket, $config)
    {
        $this->bucket = $bucket;
        $this->endpoint = $config['endpoint'];
        $this->securityToken = $config['securityToken'] ?? null;
        $this->timeout = $config['timeout'] ?? 3600; //有效期
        $this->domain = $config['domain'] ?? null; //自定义域名
        $this->config = $config;
        $this->ossClient = new OssClient($config['accessKeyId'], $config['accessKeySecret'], $this->endpoint, false, $this->securityToken);
        $this->ossClient->setTimeout($config['curl_timeout'] ?? 30);
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function getSecurityToken()
    {
        return $this->securityToken;
    }

    public function getAccessKeyId()
    {
        return $this->config['accessKeyId'];
    }

    public function getAccessKeySecret()
    {
        return $this->config['accessKeySecret'];
    }

    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 上传文件
     * @param $filename
     * @param $filePath
     * @param int $timeout curl超时时间，单位秒
     * @param bool $reTry 是否重试
     * @return bool
     */
    public function uploadFile($filename, $filePath, $timeout = null, $reTry = true)
    {
        $i = 0;
        $tryNum = self::RETRY_NUM;
        if (!$reTry) {
            $tryNum = 1;
        }
        while ($i++ < $tryNum) {
            try {
                if ($timeout > 0) {
                    $this->ossClient->setTimeout($timeout);
                }
                $this->ossClient->uploadFile($this->bucket, $filename, $filePath);
                return true;
            } catch (OssException $e) {
                //超时可以重试
                if (strpos($e->getMessage(), 'timed out after') !== false) {
                    continue;
                }
                $this->error = $e;
                return false;
            }
        }
        $this->error = new OssException('upload file timed out after ' . $tryNum . ' times');
        return false;
    }

    /**
     * 上传文件
     * @param string $filename 文件名称
     * @param string $content 文件内容
     * @param int $timeout 超时时间
     * @param bool $reTry 是否重试
     * @return bool
     */
    public function putObject($filename, $content, $timeout = 0, $reTry = true)
    {


        $i = 0;
        $tryNum = self::RETRY_NUM;
        if (!$reTry) {
            $tryNum = 1;
        }
        while ($i++ < $tryNum) {
            try {
                if ($timeout > 0) {
                    $this->ossClient->setTimeout($timeout);
                }
                $this->ossClient->putObject($this->bucket, $filename, $content);
                return true;
            } catch (OssException $e) {
                //超时可以重试
                if (strpos($e->getMessage(), 'timed out after') !== false) {
                    continue;
                }
                $this->error = $e;
                return false;
            }
        }
        $this->error = new OssException('upload file timed out after ' . $tryNum . ' times');
        return false;
    }

    /**
     * 下载文件
     * @param $filename
     * @param $localfile
     * @return bool
     */
    public function downloadFile($filename, $localfile)
    {
        $options = [
            OssClient::OSS_FILE_DOWNLOAD => $localfile,
        ];
        try {
            $this->ossClient->getObject($this->bucket, $filename, $options);
            return true;
        } catch (OssException $e) {
            $this->error = $e;
            return false;
        }
    }

    /**
     * 删除文件
     * @param $filename
     * @return bool
     */
    public function deleteFile($filename)
    {
        try {
            $this->ossClient->deleteObject($this->bucket, $filename);
            return true;
        } catch (OssException $e) {
            $this->error = $e;
            return false;
        }
    }

    /**
     * 批量删除文件
     * @param $filenames
     * @return bool
     * @throws null
     */
    public function deleteFiles($filenames)
    {
        try {
            $this->ossClient->deleteObjects($this->bucket, $filenames);
            return true;
        } catch (OssException $e) {
            $this->error = $e;
            return false;
        }
    }

    /**
     * 获取文件临时访问链接
     * @param $filename
     * @param null $timeout
     * @return bool|mixed
     */
    public function getUrl($filename, $timeout = null)
    {
        try {
            $signedUrl = $this->ossClient->signUrl($this->bucket, $filename, $timeout ?? $this->timeout);
        } catch (OssException $e) {
            $this->error = $e;
            return false;
        }
        if ($this->domain) {
            $arr = explode('//', $signedUrl);
            $arr1 = explode('/', $arr[1]);
            $signedUrl = str_replace($arr[0] . '//' . $arr1[0], $this->domain, $signedUrl);
        }
        return $signedUrl;
    }
}