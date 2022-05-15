<?php
namespace qmmontitor\abstracts;

use qmmontitor\command\Singleton;
use Swoole\Process;
use Swoole\Process\Manager;

abstract class AbstractProcess
{
    use Singleton;

    protected $process = null;

    /**
     * 处理信号
     * 在工作进程中应当监听 SIGTERM 信号，当主进程需要终止该进程时，会向此进程发送 SIGTERM 信号。
     * 如果工作进程未监听 SIGTERM 信号，底层会强行终止当前进程，造成部分逻辑丢失。
     * @param $running
     * @param $pool
     */
    public function signal(&$running,$pool)
    {
        Process::signal(SIGTERM, function () use (&$running,$pool) {
            $running = false;
            echo "终止\n";
        });
        Process::signal(SIGKILL, function () use (&$running,$pool) {
            $running = false;
            echo "杀死\n";
        });
        Process::signal(SIGINT, function () use (&$running,$pool) {
            $running = false;
            echo "来自键盘的中断信号\n";
        });
        Process::signal(SIGQUIT, function () use (&$running,$pool) {
            $running = false;
            echo "来自键盘的离开信号\n";
        });
        Process::signal(SIGTSTP, function () use (&$running,$pool) {
            $running = false;
            echo "来自终端的停止信号\n";
        });
    }

    /**
     * 设置进程名称
     * @param string $processName
     */
    public function setProcessName(string $processName)
    {
        swoole_set_process_name($processName);
    }

    public function start()
    {
        $this->getProcess()->start();
    }


    public function getProcess()
    {
        return $this->process;
    }

    abstract public function createProcess(...$argument);

    //abstract public function executeRabbitMq(bool $enableCoroutine,string $queueName,array $amqpConfig,array $queueConfig);

}