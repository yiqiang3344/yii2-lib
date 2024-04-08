<?php

namespace yiqiang3344\yii2_lib\helper\log;


use yiqiang3344\yii2_lib\commonSms\SmsCenter;
use yiqiang3344\yii2_lib\helper\DebugBacktrace;
use yiqiang3344\yii2_lib\helper\EnvV2;
use yiqiang3344\yii2_lib\helper\Time;
use yii\base\Exception;
use yii\helpers\VarDumper;
use yii\log\LogRuntimeException;
use \Yii;
use yii\web\Request;

/**
 */
abstract class EmailTarget extends \yii\log\EmailTarget
{
    /**
     * @throws \yii\base\InvalidConfigException
     * @throws Exception
     */
    public function init()
    {
        $this->message['to'] = ['']; //不能为空，先占着
        parent::init();
    }

    abstract public function getMonitorAddressBook(): array;

    abstract public function getNotifyBizTypes(): array;

    /**
     * @throws LogRuntimeException
     * @throws Exception
     */
    public function export()
    {
        //根据业务类型确认监督人员，init()里面修改无效，初始化配置时，还没有设置责任人
        $this->message['to'] = $this->getMonitorAddressBook();
        //报错邮件只会有一封，直接修改主题，init()里面修改会取不到module
        $this->message['subject'] = EnvV2::getErrorNotifySubjectPre();
        $messages = array_map([$this, 'formatMessage'], $this->messages);

        $body = '**' . $this->message['subject'] . "**  \n" . wordwrap($messages[0], 70);

        if (!SmsCenter::instance()->sendNotify($this->message['subject'], $body, $this->message['to'], $this->getNotifyBizTypes() ?: ['common_error_notify'], ['msg_type' => 'markdown'])) {
            throw new LogRuntimeException('Unable to export log through email!');
        }
    }

    /**
     * @param array $message
     * @return string
     * @throws \Exception
     */
    public function getMessagePrefix($message)
    {
        if (Yii::$app === null) {
            return '';
        }

        $errorMsg = [
            '时间' => Time::now(),
            '主机' => gethostname(),
            '项目名' => PROJECT_NAME_ZH,
            'trace_id' => EnvV2::getRequestFloatNumber(),
        ];

        if (\Yii::$app->id == 'console') {
            if (count($_SERVER['argv']) > 2) {
                $errorMsg['参数'] = (($_SERVER['argv'][2] ?? '') . ' ' . ($_SERVER['argv'][3] ?? '') . ' ' . ($_SERVER['argv'][4] ?? ''));
            }
        } else {
            $request = Yii::$app->getRequest();
            $errorMsg['ip'] = $request instanceof Request ? $request->getUserIP() : '';
            $errorMsg['请求方式'] = \Yii::$app->request->method;
            $errorMsg['参数'] = "\n```\n" . json_encode(Yii::$app->getRequest()->getBodyParams(), true) . "\n```\n";
        }

        $message = '';
        foreach ($errorMsg as $key => $val) {
            $message .= "{$key}:{$val}  \n";
        }

        return trim($message, "\n");
    }


    /**
     * @param array $message
     * @return string
     * @throws \Exception
     * @throws \Throwable
     */
    public function formatMessage($message)
    {
        list($text, $level, $category, $timestamp) = $message;
        if ($text instanceof \Exception) {
            $this->message['subject'] .= get_class($text) . '[' . $text->getCode() . "]:" . mb_substr($text->getMessage(), 0, 140);
        }
        if (!is_string($text)) {
            // exceptions may not be serializable if in the call stack somewhere is a Closure
            if ($text instanceof \Throwable || $text instanceof \Exception) {
                $text = static::errorLog($text);
            } else {
                $text = VarDumper::export($text);
            }
        }
        $prefix = $this->getMessagePrefix($message);

        return "{$prefix}\n**detail**:\n\n{$text}\n\n";
    }

    /**
     * 处理服务器异常
     * @param \Exception $exception
     */
    public static function errorLog(\Throwable $exception)
    {
        $traceArr = [
            "- 错误信息:{$exception->getMessage()}",
            "- in " . (DebugBacktrace::handleFileName($exception->getFile() . ':' . $exception->getLine())),
            "- Stack trace:",
        ];
        $traces = DebugBacktrace::getTraces($exception->getTrace(), 0, false);
        foreach ($traces as $v) {
            $traceArr[] = '- ' . $v;
        }

        return implode("\n", $traceArr);
    }
}