<?php

namespace yiqiang3344\yii2_lib\helper;


use yii\base\Exception;
use yii\base\Model;

/**
 * 环境变量类
 * User: sidney
 * Date: 2020/1/7
 */
class Env extends Model
{
    protected static $globalAttributes = [
        'token' => ['name' => 'JWT token'],
        'user_id' => ['name' => '用户ID'],
        'sub_user_id' => ['name' => '子表用户ID'],
        'mobile' => ['name' => '手机号'],
        'app' => ['name' => 'APP标识或被嵌入的APP的标识'],
        'inner_app' => ['name' => '嵌入到的APP的标识'],
        'ua' => ['name' => '合作方标识'],
        'args' => ['name' => '请求参数'],
        'app_version' => ['name' => 'app版本号'],
        'source_type' => ['name' => '来源'],
        'os' => ['name' => '系统类型'],
        'idfv' => ['name' => 'IDFV'],
        'idfa' => ['name' => 'IDFA'],
        'imei' => ['name' => 'IMEI'],
        'device_id' => ['name' => '设备号'],
        'utm_source' => ['name' => '推广渠道'],
        'channel' => ['name' => '应用市场渠道'],
        'request_float_number' => ['name' => '请求流水号'],
        'qa_env' => ['name' => '调试环境'],
        'app_id' => ['name' => 'sdk id'],
        'sdk_version' => ['name' => 'sdk版本'],
        'os_version' => ['name' => 'os版本'],
        'oaid' => ['name' => '安卓广告ID'],
        'user_agent' => ['name' => '代理信息'],
        'encrypt_key' => ['name' => '对称加密秘钥'],
        'biz_monitor_address_book' => ['name' => '业务监控人员通讯录'],
        'biz_type' => ['name' => '业务类型'],
        'origin_response' => ['name' => '原始响应数据'],
        'trace_id' => ['name' => 'trace_id'],
    ];

    /**
     * 设置全局属性方法
     * @param $name
     * @param $value
     * @throws Exception
     */
    public static function setAttr($name, $value)
    {
        if (!isset(static::$globalAttributes[$name])) {
            throw new Exception($name . '：属性不合法');
        }
        static::$globalAttributes[$name]['value'] = $value;
    }

    public static function getAttr($name)
    {
        return static::$globalAttributes[$name]['value'] ?? null;
    }

    public static function getEnv()
    {
        return $_SERVER['WEB_ENV'] ?? getenv('WEB_ENV');
    }

    /**
     * 判断是不是测试环境
     * @return bool
     */
    public static function isTest()
    {
        return in_array(static::getEnv(), ['local', 'dev', 'test', 'qa1', 'qa2', 'qa3', 'qa4', 'sit']);
    }

    /**
     * 获取请求协议
     * @param bool $env
     * @param bool $noHttps
     * @return string
     */
    public static function getProtocol($env = false, $noHttps = false)
    {
        if ($noHttps === true) {
            return 'http://';
        }
        //根据环境来
        if ($env == true) {
            return Env::isTest() ? 'http://' : 'https://';
        }
        //根据当前协议来
        return ($_SERVER['HTTPS'] ?? '') == 'on' ? 'https://' : 'http://';
    }

    /**
     * 获取原始请求端ip，支持负载均衡和代理
     * @return string
     */
    public static function getIp()
    {
        return static::getUserRealIp();
    }

    public static function getHost()
    {
        return static::getProtocol() . ($_SERVER['HTTP_HOST'] ?? '');
    }

    /**
     * 获取token
     * @return string
     */
    public static function getToken()
    {
        return static::getAttr('token') ?? '';
    }

    /**
     * @return string
     */
    public static function getUserId()
    {
        $value = static::getAttr('user_id') ?? '';
        return (string)$value;
    }

    /**
     * @return string
     */
    public static function getSubUserId()
    {
        $value = static::getAttr('sub_user_id') ?? '';
        return (string)$value;
    }

    /**
     * @return string
     */
    public static function getMobile()
    {
        $value = static::getAttr('mobile') ?? '';
        return (string)$value;
    }

    /**
     * @return string
     */
    public static function getTraceId()
    {
        $value = static::getAttr('trace_id') ?? '';
        return (string)$value;
    }

    /**
     * @return string
     */
    public static function initTraceId()
    {
        $id = \yiqiang3344\yii2_lib\helper\Sequence::getId();
        static::setAttr('trace_id', $id);
        return $id;
    }

    /**
     * 获取应用来源类型
     * @param bool $isUp
     * @return string
     */
    public static function getApp($isUp = false)
    {
        $value = static::getAttr('app') ?? '';
        return $isUp ? strtoupper($value) : strtolower($value);
    }

    /**
     * 获取嵌入到的APP的标识，默认为APP的标识
     * @param bool $isUp
     * @return string
     */
    public static function getInnerApp($isUp = false)
    {
        $value = static::getAttr('inner_app') ?? '';
        return $isUp ? strtoupper($value) : strtolower($value);
    }

    /**
     * 获取合作方标识
     * @param bool $isUp
     * @return string
     */
    public static function getUa($isUp = false)
    {
        $value = static::getAttr('ua') ?? '';
        return $isUp ? strtoupper($value) : strtolower($value);
    }

    /**
     * 获取合作方标识
     * @return array
     */
    public static function getArgs()
    {
        $ret = static::getAttr('args') ?? [];
        return $ret;
    }

    /**
     * 获取版本号
     * @return string
     */
    public static function getAppVersion()
    {
        return static::getAttr('app_version') ?? '';
    }

    /**
     * 获取应用来源类型
     * @return string
     */
    public static function getSourceType()
    {
        $value = static::getAttr('source_type') ?? '';
        return strtolower($value);
    }

    /**
     * 获取客户端系统类型
     * @return string
     */
    public static function getOS()
    {
        $os = static::getAttr('os') ?? '';
        if ($os) {
            return strtolower($os);
        }
        $httpUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (strpos($httpUserAgent, 'iPhone') || strpos($httpUserAgent, 'iPad')) {
            $os = 'ios';
        } else if (strpos($httpUserAgent, 'Android')) {
            $os = 'android';
        } else {
            $os = 'other';
        }
        return $os;
    }

    /**
     * @return string
     */
    public static function getIdfv()
    {
        $value = static::getAttr('idfv') ?? '';
        return (string)$value;
    }

    /**
     * @return string
     */
    public static function getIdfa()
    {
        $value = static::getAttr('idfa') ?? '';
        return (string)$value;
    }

    /**
     * @return string
     */
    public static function getImei()
    {
        $value = static::getAttr('imei') ?? '';
        return (string)$value;
    }

    /**
     * 获取设备ID：IOS为idfa，安卓为imei
     * @return string
     */
    public static function getDeviceId()
    {
        return static::getAttr('device_id') ?? '';
    }

    /**
     * 获取utm_source
     * @return string
     */
    public static function getUtmSource()
    {
        $ret = static::getAttr('utm_source') ?? '';
        $ret = urldecode($ret);
        return $ret;
    }

    /**
     * 获取应用渠道
     * @return string
     */
    public static function getChannel()
    {
        return strtolower(static::getAttr('channel') ?? '');
    }

    /**
     * 获取请求流水号，一次请求全局唯一
     */
    public static function getRequestFloatNumber()
    {
        if (!static::getAttr('request_float_number')) {
            static::setAttr('request_float_number', Sequence::getId());
        }
        return static::getAttr('request_float_number');
    }

    public static function getQaEnv()
    {
        return static::getAttr('qa_env') ?? '';
    }

    /**
     * @return string
     */
    public static function getAppId()
    {
        $value = static::getAttr('app_id') ?? '';
        return (string)$value;
    }

    /**
     * @return string
     */
    public static function getSdkVersion()
    {
        $value = static::getAttr('sdk_version') ?? '';
        return (string)$value;
    }

    /**
     * @return string
     */
    public static function getOsVersion()
    {
        $value = static::getAttr('os_version') ?? '';
        return (string)$value;
    }

    /**
     * @return string
     */
    public static function getOaid()
    {
        $value = static::getAttr('oaid') ?? '';
        return (string)$value;
    }

    /**
     * @return string
     */
    public static function getUserAgent()
    {
        $value = static::getAttr('user_agent') ?? '';
        return (string)$value;
    }

    /**
     * 获取秘钥
     * @return string
     */
    public static function getEncryptKey()
    {
        return static::getAttr('encrypt_key') ?? '';
    }

    /**
     * 获取业务监控人员邮箱列表
     * @return array
     */
    public static function getBizMonitorAddressBook()
    {
        return static::getAttr('biz_monitor_address_book') ?? [];
    }

    /**
     * 获取业务类型
     * @return string
     */
    public static function getBizType()
    {
        return static::getAttr('biz_type') ?? '';
    }

    public static function getUri()
    {
        if (\Yii::$app->id == 'console') {
            $ret = (\Yii::$app->controller->id ?? '') . '/' . (\Yii::$app->controller->action->id ?? '');
        } else {
            $ret = \Yii::$app->request->getPathInfo();
        }
        return $ret;
    }

    /**
     * 获取原始请求端ip，支持负载均衡和代理
     * @return string
     */
    public static function getUserRealIp()
    {
        $realip = '';
        try {
            if (isset($_SERVER)) {
                if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } else if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                    $realip = $_SERVER['HTTP_CLIENT_IP'];
                } else {
                    $realip = $_SERVER['REMOTE_ADDR'] ?? '';
                }
            } else {
                if (getenv('HTTP_X_FORWARDED_FOR')) {
                    $realip = getenv('HTTP_X_FORWARDED_FOR');
                } else if (getenv('HTTP_CLIENT_IP')) {
                    $realip = getenv('HTTP_CLIENT_IP');
                } else {
                    $realip = getenv('REMOTE_ADDR') ?: '';
                }
            }
        } catch (\Throwable $e) {
            $realip = '';
        }
        $realip = explode(",", $realip);
        return $realip[false];
    }

    /**
     * 获取错误通知主题前缀
     * @return string
     */
    public static function getErrorNotifySubjectPre()
    {
        $subject = '[' . static::getEnv() . '][' . PROJECT_NAME . ']';
        $subject .= '[' . \Yii::$app->id . '][' . static::getUri() . ']';
        return $subject;
    }

    /**
     * 获取原始响应数据
     * @return mixed
     */
    public static function getOriginResponse()
    {
        return static::getAttr('origin_response') ?? null;
    }
}