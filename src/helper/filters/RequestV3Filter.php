<?php

namespace yiqiang3344\yii2_lib\helper\filters;

use yiqiang3344\yii2_lib\helper\encrypt\Encrypt;
use yiqiang3344\yii2_lib\helper\EnvV2;
use yiqiang3344\yii2_lib\helper\StringHelper;
use yii\base\ActionFilter;
use Yii;
use yii\base\Exception;
use yii\base\UserException;

/**
 * v3版本请求过滤器
 * 验签秘钥及args解密秘钥通过header的encrypt-key字段做rsa私钥解密获取
 * 参见文档 https://www.tapd.cn/20090981/markdown_wikis/show/#1120090981001007769
 */
abstract class RequestV3Filter extends ActionFilter
{
    //header字段规范（可重写）
    public static $headerRule = [
        'encrypt-key' => ['must' => true],
        'trace-id' => ['must' => false, 'alias' => 'request_float_number'],
        'request-float-number' => ['must' => false],
        'source-type' => ['must' => true],
        'app' => ['must' => true],
        'inner-app' => ['must' => true],
        'token' => ['must' => false],
        'device-id' => ['must' => true],
        'app-id' => ['must' => false],
        'sdk-version' => ['must' => false],
        'app-version' => ['must' => false],
        'os' => ['must' => false],
        'os-version' => ['must' => false],
        'channel' => ['must' => false],
        'imei' => ['must' => false],
        'oaid' => ['must' => false],
        'idfv' => ['must' => false],
        'idfa' => ['must' => false],
        'user-agent' => ['must' => false],
        'utm-source' => ['must' => false],
    ];

    /**
     * 不需要验签的路由
     */
    abstract public function getNoSignWhiteRoutes(): array;

    /**
     * 参数不需要加密的路由
     */
    abstract public function getArgsNoEncryptWhiteRoutes(): array;

    /**
     * 根据ua获取对应RSA私钥（需实现）
     * @param string $ua
     * @return string
     */
    abstract public function getPriKey(string $ua): string;

    /**
     * 获取参数加密开关（需实现）
     */
    abstract public function getArgsEncryptSwitch(): bool;

    /**
     * 获取验签开关（需实现）
     */
    abstract public function getSignSwitch(): bool;

    /**
     * 根据自身项目初始化Env变量（需实现）
     * @param array $envs
     */
    abstract public function initEnv(array $envs): void;

    /**
     * @param \yii\base\Action $action
     * @return bool
     * @throws \Exception
     * @throws \yii\base\Exception
     * @throws \yii\base\UserException
     */
    public function beforeAction($action)
    {
        //header检查，并返回需初始化的环境变量
        $envs = $this->checkHeader();

        //body参数检查
        $params = $this->checkParams();

        $envs['args'] = $this->decryptArgs($params);

        //环境变量初始化
        $this->initEnv($envs);

        return true;
    }

    /**
     * @throws UserException
     * @throws Exception
     */
    protected function checkHeader()
    {
        $header = \Yii::$app->request->getHeaders();

        $envs = [];
        foreach (static::$headerRule as $k => $row) {
            if ($row['must'] && empty($header->get($k))) {
                throw new UserException($k . '不能为空', -2);
            }
            $v = $header->get($k, '');
            $envs[$row['alias'] ?? StringHelper::lineToUnder($k)] = $v;
        }

        return $envs;
    }

    /**
     * 检查body参数及验签
     * @return array|null
     * @throws Exception
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */
    protected function checkParams()
    {
        $params = \Yii::$app->request->getBodyParams();

        if (empty($params['ua'])) {
            throw new UserException('参数不能为空：ua', -3);
        }

        if (in_array(Yii::$app->request->getPathInfo(), $this->getNoSignWhiteRoutes())) {
            return $params;
        }

        if (empty($params['sign'])) {
            throw new UserException('参数不能为空：sign', -3);
        }

        //验签
        if (!$this->sign($params)) {
            throw new UserException('验签失败', -3);
        }

        return $params;
    }


    /**
     * 验签，只根据controller id及action id做验签
     * @param $params
     * @return bool
     * @throws Exception
     */
    protected function sign($params)
    {
        if (!$this->getSignSwitch()) {
            return true;
        }

        $key = Yii::$app->request->getHeaders()->get('encrypt-key');
        if (!$key) {
            throw new UserException('加密秘钥不能为空', -3);
        }
        //解密秘钥
        $key = Encrypt::rsaPriDecrypt($key, $this->getPriKey($params['ua']));
        if (!$key) {
            throw new UserException('加密秘钥解密失败', -3);
        }

        $sign = Encrypt::getSignByUa($params['ua'], $key, $params['args'], Yii::$app->controller->id . '/' . Yii::$app->controller->action->id);
        if ($sign != $params['sign']) {
            return false;
        }
        return true;
    }

    protected function decryptArgs($params)
    {
        $ret = $params['args'];
        if (in_array(Yii::$app->request->getPathInfo(), $this->getArgsNoEncryptWhiteRoutes())) {
            return $ret;
        }
        if ($this->getArgsEncryptSwitch()) {
            if (!is_string($params['args'])) {
                throw new UserException('args参数格式不合法', -3);
            }

            $key = Yii::$app->request->getHeaders()->get('encrypt-key');
            if (!$key) {
                throw new UserException('加密秘钥不能为空', -3);
            }
            $key = Encrypt::rsaPriDecrypt($key, $this->getPriKey($params['ua']));
            if (!$key) {
                throw new UserException('加密秘钥解密失败', -3);
            }
            EnvV2::setAttr('encrypt_key', $key);
            try {
                $ret = json_decode(Encrypt::aesEcbDecrypt($params['args'], $key), true);
            } catch (\Exception $e) {
                throw new UserException('args参数解密失败', -3);
            }
        } else {
            //可能传的json字符串
            $retArr = json_decode($ret, true);
            if (is_array($retArr)) {
                $ret = $retArr;
            }
        }
        return $ret;
    }
}