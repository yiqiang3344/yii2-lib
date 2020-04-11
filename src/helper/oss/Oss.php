<?php

namespace yiqiang3344\yii2_lib\helper\oss;

use OSS\Core\OssException;
use OSS\OssClient;

/**
 * 通用OSS
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
 */
class Oss
{
    protected $endpoint; //域名
    protected $bucket; //存储空间名
    protected $ossClient; //oss客户端对象
    protected $securityToken;//临时访问url的token
    protected $timeout; //临时访问url的有效期
    protected $domain; //临时访问url的自定义域名

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
        $this->ossClient = new OssClient($config['accessKeyId'], $config['accessKeySecret'], $this->endpoint, false, $this->securityToken);
    }

    /**
     * 上传文件
     * @param $filename
     * @param $filePath
     * @return bool
     */
    public function uploadFile($filename, $filePath)
    {
        try {
            $this->ossClient->uploadFile($this->bucket, $filename, $filePath);
            return true;
        } catch (OssException $e) {
            $this->error = $e;
            return false;
        }
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
     * @return bool|mixed
     */
    public function getUrl($filename)
    {
        try {
            $signedUrl = $this->ossClient->signUrl($this->bucket, $filename, $this->timeout);
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