<?php
namespace qmmonitor\process;

use qmmonitor\command\Command;
use qmmonitor\core\RabbitMqManager;
use qmmonitor\extra\abstracts\AbstractProcess;
use qmmonitor\extra\traits\Singleton;
use Swoole\Process\Manager;
use Swoole\Process\Pool;

class ProcessManager extends AbstractProcess
{
    use Singleton;
    /**
     * 创建swoole Manager 在 v4.5.3 以上的版本可用。
     * @param mixed ...$argument
     */
    public function createProcess(...$arguments)
    {
        $this->process = new Manager(...$arguments);
    }

    /**
     * 多进程方式运行rabbitMq
     * @param bool $enableCoroutine 是否开启协程
     * @param string $queueName 队列名称
     * @param array $amqpConfig mq连接配置参数
     * @param array $nowQueueConfig 当前队列配置参数
     * @return Manager|null
     */
    public function executeRabbitMq(bool $enableCoroutine, string $queueName, array $amqpConfig, array $nowQueueConfig)
    {
        /**@var $pm \Swoole\Process\Manager **/
        $pm = $this->process;
        $pm->addBatch($nowQueueConfig['count'],function (Pool $pool,$workerId) use ($queueName,$amqpConfig,$nowQueueConfig){
            //设置信号
            $this->signal();
            $pid = posix_getpid();
            $processName = $this->getProcessName($queueName,$workerId,'free',Command::$projectName);
            //默认使用free标记空闲进程
            $this->setProcessName($processName);
            echo("[Worker:{$workerId}] WorkerStarted, pid: {$pid},process:$processName" . PHP_EOL);
            //设置channel
            $channel = RabbitMqManager::getInstance($amqpConfig)->qos();
            //创建回调
            $callBack = RabbitMqManager::getInstance()
                ->consumerCallBack($channel,$nowQueueConfig,$queueName,$workerId,$pid);
            //消费
            RabbitMqManager::getInstance()->consumer($queueName, $callBack, $channel);

        },$enableCoroutine);
        $this->process = $pm;
        return $this->process;
    }
}