<?php

namespace yiqiang3344\yii2_lib\helper\encrypt;


use yii\base\Model;

/**
 * 通用加密工具类
 * User: sidney
 * Date: 2019/8/29
 */
class Encrypt extends Model
{
    /**
     * 统一加密方法 AES/CBC/PKCS5Padding
     * @param $input
     * @param $key
     * @return string
     */
    public static function encrypt($input, $key, $iv = "1234567812345678")
    {
        $encrypted = openssl_encrypt($input, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $data = base64_encode($encrypted);
        return $data;
    }

    /**
     * 统一解密函数
     * @param $sStr
     * @param $sKey
     * @return bool|string
     */
    public static function decrypt($sStr, $sKey, $iv = "1234567812345678")
    {
        $decrypted = openssl_decrypt(base64_decode($sStr), 'aes-128-cbc', $sKey, OPENSSL_RAW_DATA, $iv);
        return $decrypted;
    }

    /**
     * 统一验签方法
     * @param string $method
     * @param string $sign
     * @param array $params
     * @param string $secretKey
     * @return bool
     */
    public static function sign($sign, $params, $secretKey)
    {
        ksort($params);
        $arr = array_merge([
            'secretKey' => $secretKey,
        ], $params);
        $_sign = md5(json_encode($arr));
        if ($_sign != $sign) {
            return false;
        }
        return true;
    }

    /**
     * ua统一验签方法
     * @param $ua
     * @param $key
     * @param $args
     * @param $method
     * @return bool
     */
    public static function getSignByUa($ua, $key, $args, $method)
    {
        $signKey = "{$ua}{$key}{$ua}";
        if (is_array($args)) {
            $args = json_encode($args);
        }
        return md5("{$signKey}{$method}{$signKey}{$args}{$signKey}");
    }

    public static function rsaEncrypt($source, $type, $key)
    {
        $maxlength = 117;
        $output = '';
        while ($source) {
            $input = substr($source, 0, $maxlength);
            $source = substr($source, $maxlength);

            if ($type == 'private') {
                $ok = openssl_private_encrypt($input, $encrypted, $key);
            } else {
                $ok = openssl_public_encrypt($input, $encrypted, $key);
            }
            $output .= $encrypted;
        }
        return $output;
    }

    public static function rsaDecrypt($source, $type, $key)
    {
        $maxlength = 128;
        $output = '';
        while ($source) {
            $input = substr($source, 0, $maxlength);
            $source = substr($source, $maxlength);
            if ($type == 'private') {
                $ok = openssl_private_decrypt($input, $out, $key);
            } else {
                $ok = openssl_public_decrypt($input, $out, $key);
            }
            $output .= $out;
        }
        return $output;
    }

    /**
     * RSA公钥加密
     * @param $source string 数据
     * @param $key string 公钥
     * @return string
     */
    public static function rsaPubEncrypt($source, $key)
    {
        return base64_encode(self::rsaEncrypt($source, 'public', $key));
    }

    /**
     * RSA私钥加密
     * @param $source string 数据
     * @param $key string 私钥
     * @return string
     */
    public static function rsaPriEncrypt($source, $key)
    {
        return base64_encode(self::rsaEncrypt($source, 'private', $key));
    }

    /**
     * RSA私钥解密
     * @param $source string 数据
     * @param $key string 私钥
     */
    public static function rsaPriDecrypt($source, $key)
    {
        return self::rsaDecrypt(base64_decode($source), 'private', $key);
    }

    /**
     * RSA私钥解密
     * @param $source string 数据
     * @param $key string 私钥
     * @return false|string
     */
    public static function rsaPubDecrypt($source, $key)
    {
        return self::rsaDecrypt(base64_decode($source), 'public', $key);
    }

    /**
     * AES(AES/ECB/PKCS5Padding)加密
     * @param $data string 数据
     * @param $key string AES密钥
     * @return string
     */
    public static function aesEcbEncrypt($data, $key)
    {
        $res = openssl_encrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return ($res === false) ? $res : base64_encode($res);
    }

    /**
     * AES(AES/ECB/PKCS5Padding)解密
     * @param $data string 数据
     * @param $key string AES密钥
     * @return string
     */
    public static function aesEcbDecrypt($data, $key)
    {
        $data = base64_decode($data);
        return openssl_decrypt($data, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }
}