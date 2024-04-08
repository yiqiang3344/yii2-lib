<?php

namespace yiqiang3344\yii2_lib\helper;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use yii\base\Exception;
use yii\base\UserException;
use yii\helpers\Json;
use yii\redis\Connection;

/**
 * 用户jwt统一类
 */
class UserJwt
{
    public static $expireTime = 86400 * 7; // 过期时间7天
    public static $expireTimeMonth = 86400*30; // 过期时间30天

    /**
     * 配合redis缓存才能完全控制token的有效期，不配置redis则不会缓存
     * @return Connection
     */
    protected static function getRedis()
    {
        return null;
    }

    /**
     * @param $uid
     * @param $app
     * @return string
     */
    protected static function generateKey($uid, $app)
    {
        return "jwt:{$uid}:{$app}";
    }

    protected static function getHeader($jwt)
    {
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            throw new Exception('token不合法');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        return (array)Jwt::jsonDecode(Jwt::urlsafeB64Decode($headb64));
    }

    protected static function getBody($jwt)
    {
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            throw new Exception('token不合法');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        return (array)Jwt::jsonDecode(Jwt::urlsafeB64Decode($bodyb64));
    }

    /**
     * 秘钥生成方法，可自定义重写
     * @param array $body jwt的body参数
     * @param bool $encode 加密还是解密
     * @return string
     * @throws Exception
     */
    protected static function generateSecret($body, $encode = true)
    {
        $body['uid'] = $body['uid'] ?? $body['mobile']; //兼容历史，只有mobile
        if (empty($body['app'])) {
            throw new Exception('app不能为空');
        }
        if (empty($body['uid'])) {
            throw new Exception('uid不能为空');
        }

        //校验时走缓存获取秘钥，可统一控制token有效期
        if (!$encode && static::getRedis()) {
            $cacheValue = static::getRedis()->get(static::generateKey($body['uid'], $body['app']));
            $cacheValue = Json::decode($cacheValue);
            if (isset($cacheValue['userSecret'])) {
                return $cacheValue['userSecret'];
            }
            throw new ExpiredException('token已失效');
        }

        //不用缓存的情况下，秘钥根据app和uid生成，安全性降低
        return md5($body['app'] . $body['uid'] . 'A$6ziWee9JEJDaUn');
    }

    /**
     * 生成 token
     * @param $uid string 用户标识，可以是手机号或者用户ID
     * @param $app string 业务标识，一般是app
     * @param array $params 额外参数，不能和固定参数重复，否则会被覆盖
     * @param array $head
     * @return string
     * @throws Exception
     */
    public static function generateToken($uid, $app, $params = [], $head = [])
    {
        if (!$uid) {
            throw new Exception('uid不能为空');
        }
        if (!$app) {
            throw new Exception('app不能为空');
        }
        $head = array_merge([
            'version' => 1,
        ], $head);

        $userSecret = static::generateSecret([
            'uid' => $uid,
            'app' => $app,
        ]);

        // 这几个app 30 天过期
        $expireTime = ($app == "xyf" || $app == "xyf01" || $app == "fxk" || $app == "cxh") ? static::$expireTimeMonth : static::$expireTime;

        $payload = array_merge([
            'uid' => $uid,
            'app' => $app,
//            'iat' => time(), //TODO 暂时不能加，因为其他系统JWT没加延时时间，会校验失败，等所有系统都统一使用此类的解密方法时再加
            'exp' => time() + $expireTime,
        ], $params);

        $token = JWT::encode($payload, $userSecret, 'HS256', null, $head);

        if (static::getRedis()) {
            static::getRedis()->setex(static::generateKey($uid, $app), $expireTime, json_encode(array_merge($payload, [
                'token' => $token,
                'userSecret' => $userSecret,
            ])));
        }
        return $token;
    }

    /**
     * 校验并解析token，成功则返回payload的信息
     * @param $token
     * @param null $app 兼容现有逻辑，后续不需要此参数
     * @return array
     * @throws UserException|Exception
     */
    public static function validateToken($token, $app = null)
    {
        $body = static::getBody($token);
        if (!isset($body['app'])) {
            if (!$app) {
                throw new Exception('app不能为空');
            }
            $body['app'] = $app;
        }
        try {
            JWT::$leeway = 10; //服务器之间的时间差，预计10秒
            $decoded = (array)JWT::decode($token, static::generateSecret($body, false), ['HS256']);
        } catch (ExpiredException $e) {
            throw new UserException('token已失效');
        } catch (\Throwable $e) {
            throw new UserException('token解析失败');
        }
        $decoded['uid'] = $decoded['uid'] ?? $decoded['mobile']; //TODO 兼容历史使用mobile逻辑
        return $decoded;
    }

    /**
     * 解析token数据，token无效则返回空数组
     * @param $token
     * @param $app string 业务标识，一般是app
     * @return array
     */
    public static function parseToken($token, $app)
    {
        if (!$token) {
            return [];
        }
        try {
            $data = static::validateToken($token, $app);
        } catch (\Throwable $e) {
            $data = [];
        }
        return $data;
    }

    /**
     * 清除token
     * @param $uid
     * @param $app string 业务标识，一般是app
     * @return void
     */
    public static function clearTokenCache($uid, $app)
    {
        static::getRedis()->del(static::generateKey($uid, $app));
    }
}