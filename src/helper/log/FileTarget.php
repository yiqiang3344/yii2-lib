<?php

namespace yiqiang3344\yii2_lib\helper\log;

use Yii;

/**
 * Class FileTarget
 */
class FileTarget extends \yii\log\FileTarget
{
    public $maxFileSize = 512 * 1024; //日志最大512M
    public $maxLogFiles = 10; //日志文件最多10个
    public $ignoreLog = false;
    public $logFileParams = null;
    public $rotateByCopy = false;

    /**
     * @param array $message
     * @return string
     * @throws \Exception
     * @throws \Throwable
     */
    public function formatMessage($message)
    {
        if ($this->ignoreLog) {
            return '';
        }
        return parent::formatMessage($message);
    }

    /**
     * @return void
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\log\LogRuntimeException
     */
    public function export()
    {
        if ($this->ignoreLog) {
            return;
        }
        if (is_array($this->logFileParams)) {
            $this->logFile = Yii::getAlias($this->logFileParams['base_path'] . '/' . date($this->logFileParams['format']) . '.log');
        }
        parent::export();
    }
}
