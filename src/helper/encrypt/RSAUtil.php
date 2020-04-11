<?php
/**
 * Created by PhpStorm.
 * User: dengpeng.zdp
 * Date: 2015/9/28
 * Time: 19:11
 */

namespace yiqiang3344\yii2_lib\helper\encrypt;

/**
 * RSA加密工具类
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
 */
class RSAUtil
{
    public static function signSafe($data, $privateKeyFilePath, $signatureAlg = OPENSSL_ALGO_SHA256)
    {
        $priKey = file_get_contents($privateKeyFilePath);

        $res = openssl_get_privatekey($priKey);

        openssl_sign($data, $sign, $res, $signatureAlg);
        openssl_free_key($res);
        $sign = self::encodeBase64URLSafe($sign);
        return $sign;
    }

    public static function encodeBase64URLSafe($string)
    {
        $data = base64_encode($string);
        $data = str_replace(array('+', '/', '='), array('-', '_', ''), $data);
        return $data;
    }

    public static function verifySafe($data, $sign, $rsaPublicKeyFilePath, $signatureAlg = OPENSSL_ALGO_SHA256)
    {
        $pubKey = file_get_contents($rsaPublicKeyFilePath);
        $res = openssl_get_publickey($pubKey);
        $ret = (bool)openssl_verify($data, self::decodeBase64URLSafe($sign), $res, $signatureAlg);
        return $ret;
    }

    public static function decodeBase64URLSafe($string)
    {
        $data = str_replace(array('-', '_'), array('+', '/'), $string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }

    /**
     * 加签
     * @param string $data 要加签的数据
     * @param string $privateKeyFilePath 私钥文件路径
     * @param int $signatureAlg 签名算法
     * @return string 签名
     */
    public static function sign($data, $privateKeyFilePath, $signatureAlg = OPENSSL_ALGO_SHA1)
    {
        $priKey = file_get_contents($privateKeyFilePath);
        $res = openssl_get_privatekey($priKey);
        openssl_sign($data, $sign, $res, $signatureAlg);
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 验签
     * @param string $data 用来加签的数据
     * @param string $sign 加签后的结果
     * @param string $rsaPublicKeyFilePath 公钥文件路径
     * @param int $signatureAlg 签名算法
     * @return bool 验签是否成功
     */
    public static function verify($data, $sign, $rsaPublicKeyFilePath, $signatureAlg = OPENSSL_ALGO_SHA1)
    {
        //读取公钥文件
        $pubKey = file_get_contents($rsaPublicKeyFilePath);

        //转换为openssl格式密钥
        $res = openssl_get_publickey($pubKey);

        //调用openssl内置方法验签，返回bool值
        $result = (bool)openssl_verify($data, base64_decode($sign), $res, $signatureAlg);

        //释放资源
        openssl_free_key($res);

        return $result;
    }


    /**
     * rsa加密
     * @param string $data 要加密的数据
     * @param string $pubKeyFilePath 公钥文件路径
     * @return string 加密后的密文
     */
    public static function rsaEncrypt($data, $pubKeyFilePath)
    {
        //读取公钥文件
        $pubKey = file_get_contents($pubKeyFilePath);
        //转换为openssl格式密钥
        $res = openssl_get_publickey($pubKey);

        $maxlength = RSAUtil::getMaxEncryptBlockSize($res);
        $output = '';
        while ($data) {
            $input = substr($data, 0, $maxlength);
            $data = substr($data, $maxlength);
            openssl_public_encrypt($input, $encrypted, $pubKey);
            $output .= $encrypted;
        }
        $encryptedData = base64_encode($output);
        return $encryptedData;
    }

    /**
     * 解密
     * @param string $data 要解密的数据
     * @param string $privateKeyFilePath 私钥文件路径
     * @return string 解密后的明文
     */
    public static function rsaDecrypt($data, $privateKeyFilePath)
    {
        //读取私钥文件
        $priKey = file_get_contents($privateKeyFilePath);
        //转换为openssl格式密钥
        $res = openssl_get_privatekey($priKey);
        $data = base64_decode($data);
        $maxlength = RSAUtil::getMaxDecryptBlockSize($res);
        $output = '';
        while ($data) {
            $input = substr($data, 0, $maxlength);
            $data = substr($data, $maxlength);
            openssl_private_decrypt($input, $out, $res);
            $output .= $out;
        }
        return $output;
    }

    /**
     *根据key的内容获取最大加密lock的大小，兼容各种长度的rsa keysize（比如1024,2048）
     * 对于1024长度的RSA Key，返回值为117
     * @param $keyRes
     * @return float
     */
    public static function getMaxEncryptBlockSize($keyRes)
    {
        $keyDetail = openssl_pkey_get_details($keyRes);
        $modulusSize = $keyDetail['bits'];
        return $modulusSize / 8 - 11;
    }

    /**
     * 根据key的内容获取最大解密block的大小，兼容各种长度的rsa keysize（比如1024,2048）
     * 对于1024长度的RSA Key，返回值为128
     * @param $keyRes
     * @return float
     */
    public static function getMaxDecryptBlockSize($keyRes)
    {
        $keyDetail = openssl_pkey_get_details($keyRes);
        $modulusSize = $keyDetail['bits'];
        return $modulusSize / 8;
    }


}