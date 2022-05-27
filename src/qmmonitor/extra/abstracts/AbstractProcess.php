<?php
namespace qmmonitor\extra\abstracts;

use qmmonitor\command\Command;
use qmmonitor\extra\traits\Singleton;
use Swoole\Process;
use Swoole\Process\Manager;
use swoole_process;

abstract class AbstractProcess
{
    use Singleton;

    protected $process = null;

    public static $isRunning = true;

    /**
     * 处理信号
     * 在工作进程中应当监听 SIGTERM 信号，当主进程需要终止该进程时，会向此进程发送 SIGTERM 信号。
     * 如果工作进程未监听 SIGTERM 信号，底层会强行终止当前进程，造成部分逻辑丢失。
     */
    protected function signal()
    {
        pcntl_signal(SIGHUP,  function($signo) {
            self::$isRunning = false;
            //echo "{$signo}SIGHUP信号处理器被调用 ".PHP_EOL;
        });
        pcntl_signal(SIGTERM,  function($signo) {
            self::$isRunning = false;
            //echo "{$signo}停止信号".PHP_EOL;
        });
        pcntl_signal(SIGTSTP,  function($signo) {
            self::$isRunning = false;
            //echo "{$signo}来自终端的停止信号 ".PHP_EOL;
        });
        /*pcntl_signal(SIGINT,  function($signo) {
            self::$isRunning = false;
            echo "{$signo}来自键盘的中断信号 ".PHP_EOL;//ctrl+C
        });*/
    }

    /**
     * 设置进程名称
     * @param string $processName
     */
    public function setProcessName(string $processName)
    {
        swoole_set_process_name($processName);
    }

    /**
     * 获取当前进程名称
     * @param string $queueName
     * @param int $workerId
     * @param $consumerStatus
     * @return string
     */
    public function getProcessName(string $queueName,int $workerId,$consumerStatus) : string
    {
        $applicationName = Command::APPLICATION_NAME;
        $processName = "php-work-{$applicationName}-{$queueName}-{$workerId}-{$consumerStatus}}";
        return $processName;
    }

    public function start()
    {
        $this->getProcess()->start();
    }

    public function getProcess()
    {
        return $this->process;
    }

    abstract public function createProcess(...$arguments);

    //abstract public function executeRabbitMq(bool $enableCoroutine,string $queueName,array $amqpConfig,array $queueConfig);

}