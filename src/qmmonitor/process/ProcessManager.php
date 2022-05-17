<?php
namespace qmmonitor\process;

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
     * @param bool $enableCoroutine
     * @param string $queueName
     * @param array $amqpConfig
     * @param array $nowQueueConfig
     * @return Manager|null
     */
    public function executeRabbitMq(bool $enableCoroutine, string $queueName, array $amqpConfig, array $nowQueueConfig)
    {
        /**@var $pm \Swoole\Process\Manager **/
        $pm = $this->process;
        $pm->addBatch($nowQueueConfig['count'],function (Pool $pool,$workerId) use ($queueName,$amqpConfig,$nowQueueConfig){
            //防止异常退出
            //$running = true;
            $pid = posix_getpid();
            //$this->signal($running, $pool);
            $processName = "php-work-{$queueName}-{$workerId}";
            $this->setProcessName("php-work-{$queueName}-{$workerId}");
            echo("[Worker:{$workerId}] WorkerStarted, pid: {$pid},process:$processName" . PHP_EOL);
            //创建回调
            $callBack = RabbitMqManager::getInstance($amqpConfig)
                ->consumerCallBack($nowQueueConfig,$queueName,$workerId,$pid);
            //设置channel并消费
            $channel = RabbitMqManager::getInstance()->qos();
            RabbitMqManager::getInstance()->consumer($queueName, $callBack,$channel);
        },$enableCoroutine);
        $this->process = $pm;
        return $this->process;
    }
}