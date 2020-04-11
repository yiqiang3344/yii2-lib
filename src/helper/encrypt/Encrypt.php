<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/23
 * Time: 11:50 AM
 */

namespace yiqiang3344\yii2_lib\helper\encrypt;


use yii\base\Model;

/**
 * 通用加密工具类
 * User: sidney
 * Date: 2019/8/29
 * @since 1.0.0
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



    public static function PaddingPKCS7($input) {
        $srcdata = $input;
        $block_size = mcrypt_get_block_size ( 'tripledes', 'ecb' );
        $padding_char = $block_size - (strlen ( $input ) % $block_size);
        $srcdata .= str_repeat ( chr ( $padding_char ), $padding_char );
        return $srcdata;
    }

    public static function encryptOld($string, $key) {
        $string = static::PaddingPKCS7 ( $string );

        $cipher_alg = MCRYPT_TRIPLEDES;
        $iv = mcrypt_create_iv ( mcrypt_get_iv_size ( $cipher_alg, MCRYPT_MODE_ECB ), MCRYPT_RAND );

        $encrypted_string = mcrypt_encrypt ( $cipher_alg, $key, $string, MCRYPT_MODE_ECB, $iv );
        $des3 = bin2hex ( $encrypted_string );

        return $des3;
    }
}