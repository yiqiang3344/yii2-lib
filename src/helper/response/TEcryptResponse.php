<?php

namespace yiqiang3344\yii2_lib\helper\response;


use yiqiang3344\yii2_lib\helper\encrypt\Encrypt;
use yiqiang3344\yii2_lib\helper\EnvV2;
use yii\base\Exception;
use yii\base\UserException;

/**
 * 请求控制器集成响应数据加密
 */
trait TEcryptResponse
{
    /**
     * 获取参数加密开关（需实现）
     */
    abstract static public function getResponseEncryptSwitch(): bool;

    protected static function ecrypt($data)
    {
        //如果传参不加密，响应也不能加密
        if (!static::getResponseEncryptSwitch()) {
            return $data;
        }
        $key = EnvV2::getEncryptKey();
        if (!$key) {
            throw new UserException('加密秘钥不存在', -2);
        }
        try {
            $ret = Encrypt::aesEcbEncrypt(json_encode(is_null($data) ? (new \stdClass()) : $data, JSON_UNESCAPED_UNICODE), $key);
        } catch (\Exception $e) {
            throw new Exception('响应数据加密失败');
        }
        EnvV2::setAttr('origin_response', $data);
        return $ret;
    }


    public static function success($data = null)
    {
        return parent::success(static::ecrypt($data));
    }
}