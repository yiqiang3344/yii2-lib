<?php

namespace xyf\lib\deamon;

use Swoole\Process;

class Deamon
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var callable 获取进程限制数方法
     */
    private $limitNumFunc;

    /**
     * @var callable 执行回调方法
     */
    private $callback;

    /**
     * @var Command[]
     */
    private $commands = [];

    /**
     * @var Worker[]
     */
    private $workers = [];

    /**
     * @var int 重载worker时间间隔
     */
    private $reloadTimeInterval;

    /**
     * @var int 最后重载worker时间
     */
    private $lastReloadTime;

    public function __construct(array $config, callable $callback, callable $limitNumFunc)
    {
        $this->config = $config;
        $this->callback = $callback;
        $this->limitNumFunc = $limitNumFunc;
        $this->reloadTimeInterval = $this->config['reload_time'] ?? 60;//重载时间间隔，单位秒
        $this->lastReloadTime = time();
    }

    public function run()
    {
        $this->loadWorkers();

        //给信号绑定处理方法 kill -1 PID
        pcntl_signal(SIGHUP, function () {
            $this->log("收到重载配置信号", 'deamon_reload');
            $this->loadWorkers();
            $this->log("重载配置完成", 'deamon_reload');
        });

        $this->waitAndRestart();
    }

    /**
     * 解析配置文件
     */
    private function parseConfig()
    {
        try {
            $limitNum = ($this->limitNumFunc)();
        } catch (\Throwable $e) {
            $this->log('进程限制数过去失败:' . $this->exceptionInfo($e), 'deamon_refresh');
            return;
        }
        //先把开关全部打开
        foreach ($this->commands as $command) {
            $command->enable = true;
        }
        $currentNum = count($this->commands);

        //再根据限制调整有效进程数
        if ($limitNum < $currentNum) {
            for ($i = 0; $i < $currentNum - $limitNum; $i++) {
                $this->commands[$limitNum + $i]->enable = false;
            }
        } elseif ($limitNum > $currentNum) {
            for ($i = 0; $i < $limitNum - $currentNum; $i++) {
                $this->commands[$currentNum + $i] = new Command([
                    'id' => $currentNum + $i + 1,
                    'enable' => true,
                ]);
            }
        }
    }

    /**
     * 加载 workers
     */
    private function loadWorkers()
    {
        $this->lastReloadTime = time();
        $this->parseConfig();
        foreach ($this->commands as $command) {
            if ($command->enable) {
                $this->log("启用 {$command->id}", 'deamon_reload');
                $this->startWorker($command);
            } else {
                $this->log("停用 {$command->id}", 'deamon_reload');
                $this->stopWorker($command);
            }
        }
    }

    /**
     * 收回进程并重启
     */
    private function waitAndRestart()
    {
        while (1) {
            pcntl_signal_dispatch();
            if (time() - $this->lastReloadTime > $this->reloadTimeInterval) {
                $this->loadWorkers();
            }
            if ($ret = Process::wait(false)) {

                $retPid = intval($ret["pid"] ?? 0);
                $index = $this->getIndexOfWorkerByPid($retPid);

                if (false !== $index) {
                    if ($this->workers[$index]->stopping) {
                        $this->log("移除守护 {$this->workers[$index]->command->id}", 'deamon_refresh');

                        unset($this->workers[$index]);
                    } else {
                        $newPid = $this->createWorker();
                        $this->workers[$index]->pid = $newPid;

                        $this->log("重新拉起 {$this->workers[$index]->command->id}", 'deamon_refresh');
                    }
                }

            }
        }
    }

    /**
     * 启动 worker
     * @param Command $command
     */
    private function startWorker(Command $command)
    {
        $index = $this->getIndexOfWorker($command->id);
        if (false === $index) {
            $pid = $this->createWorker();

            $worker = new Worker();
            $worker->pid = $pid;
            $worker->command = $command;
            $this->workers[] = $worker;
        }
    }

    /**
     * 停止 worker
     * @param Command $command
     */
    private function stopWorker(Command $command)
    {
        $index = $this->getIndexOfWorker($command->id);
        if (false !== $index) {
            $this->workers[$index]->stopping = true;
        }
    }

    /**
     *
     * @param $commandId
     * @return bool|int|string
     */
    private function getIndexOfWorker(int $commandId)
    {
        foreach ($this->workers as $index => $worker) {
            if ($commandId == $worker->command->id) {
                return $index;
            }
        }
        return false;
    }

    /**
     * @param $pid
     * @return bool|int|string
     */
    private function getIndexOfWorkerByPid($pid)
    {
        foreach ($this->workers as $index => $worker) {
            if ($pid == $worker->pid) {
                return $index;
            }
        }
        return false;
    }

    /**
     * 创建子进程，并返回子进程 id
     * @return int
     */
    private function createWorker(): int
    {
        $process = new Process(function (Process $worker) {
            ($this->callback)();
        });
        return $process->start();
    }

    /**
     * 获取固定格式异常信息
     * @param \Exception $e
     * @return string
     */
    protected function exceptionInfo(\Throwable $e)
    {
        return $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }

    /**
     * 打印日志，可重载修改
     * @param $message
     * @param $tag
     */
    protected function log($message, $tag)
    {
        echo $message . '----' . $tag . PHP_EOL;
    }
}