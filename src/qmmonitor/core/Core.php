<?php
namespace qmmonitor\core;

use qmmonitor\exception\MonitorException;
use qmmonitor\extra\Color;
use qmmonitor\extra\pojo\JobArguments;
use qmmonitor\extra\pojo\RabbitMqQueueArguments;
use qmmonitor\extra\traits\Singleton;
use qmmonitor\helper\FileHelper;
use qmmonitor\helper\PhpHelper;
use qmmonitor\process\ProcessManager;

/**
 * Class Core
 * @package app\object\core
 */
class Core
{
    use Singleton;

    private $throwable;

    public function __construct()
    {
    }

    /**
     * 执行脚本
     * @param JobArguments $jobArguments
     * @param array $nowQueueConfig
     * @param $arguments 当前参数信息
     */
    public function runJob(JobArguments $jobArguments,array $nowQueueConfig) : bool
    {
        $jobs = $nowQueueConfig['job'];
        //设置附带的自定义参数
        $extendParams = $nowQueueConfig['extend_params'] ?? [];
        $jobArguments->setExtendParams($extendParams);
        $isSuccess = true;
        $startTime = microtime(true);
        //依次执行相关任务
        foreach ($jobs as $job) {
            try {
                //设置当前进行的任务
                $jobArguments->setJobName($job);
                $jobObject = new $job();
                $jobObject->perform($jobArguments);
                $jobObject = null;
            } catch (\Throwable $throwable) {
                $isSuccess = false;
                $this->setThrowable($throwable);
                //一个任务有问题则不会再往下执行了，以保证多个job的事务完整性及$isSuccess的准确性
                break;
            }
        }
        $endTime = microtime(true);
        if (ConfigurationManager::getInstance()->getConfig('debug')) {
            $consumerTime = PhpHelper::subtraction($startTime,$endTime);
            echo Color::info($jobArguments->getProcessName()."进程时间消费:{$consumerTime}".PHP_EOL);
        }
        return $isSuccess;
    }

    /**
     * 使用ProcessMnager的MQ消费
     */
    public function processManagerConsumerForMq()
    {
        $enableCoroutine = false;
        //获取基础配置
        $amqpConfig = ConfigurationManager::getInstance()->getConfig('amqp');
        //获取任务配置
        $queuesConfig = ConfigurationManager::getInstance()->getConfig('queue');
        //初始化,如果有注册则会执行
        $initClosure = ConfigurationManager::getInstance()->getConfig('initialize');
        if (!empty($initClosure)) {
            $initClosure();
        }
        //创建进程器
        ProcessManager::getInstance()->createProcess();
        foreach ($queuesConfig as $queueName => $nowQueueConfig) {
            ProcessManager::getInstance()->executeRabbitMq($enableCoroutine,$queueName,$amqpConfig,$nowQueueConfig);
        }
        ProcessManager::getInstance()->start();
    }

    /**
     * 生产
     * @param mixed $message
     * @param RabbitMqQueueArguments $rabbitMqQueueArguments
     * @param array $config 外部的全量配置文件
     * @return null
     * @throws \Exception
     */
    public function put($message,RabbitMqQueueArguments $rabbitMqQueueArguments,array $config = [])
    {
        //ConfigurationManager::getInstance()->loadConfig($config);
        //检测消息长度
        $this->checkLength($message);
        $runRightNow = (bool)ConfigurationManager::getInstance()->getConfig('queue_run_right_now');
        if ($runRightNow) {
            $queueName = $rabbitMqQueueArguments->getQueueName();
            //以下为兼容处理
            if (empty($queueName)) {
                //根据配置拿到相应的job信息
                $queueName = ConfigurationManager::getInstance()
                    ->getQueueByExchangeName($rabbitMqQueueArguments->getExchange(),$rabbitMqQueueArguments->getRouteKey());
                $rabbitMqQueueArguments->setQueueName($queueName);
            } else {
                //检测参数是否正常
                ConfigurationManager::getInstance()->checkQueueExist($rabbitMqQueueArguments);
            }
            //获取队列名称及Job绑定信息
            $queuesConfig = ConfigurationManager::getInstance()->getConfig('queue');
            $nowQueueConfig = $queuesConfig[$queueName] ?? [];
            if (empty($nowQueueConfig)) throw new MonitorException("当前配置queue中缺少{$queueName}的相应配置");
            //设置参数
            $jobArguments = new JobArguments();
            $jobArguments->setQueueName($queueName)
                ->setMessage($message)
                ->setConfigurationManager(ConfigurationManager::getInstance());
            if (PHP_SAPI == 'cli' && DIRECTORY_SEPARATOR == '/') {
                $jobArguments->setPid(posix_getpid());
            }
            //执行任务
            Core::getInstance()->runJob($jobArguments,$nowQueueConfig);
            return true;
        }
        $connectionConfig = ConfigurationManager::getInstance()->getConfig('amqp');
        return \qmmonitor\core\RabbitMqManager::getInstance($connectionConfig)->put($message,$rabbitMqQueueArguments);
    }

    /**
     * @return \Throwable
     */
    public function getThrowable() : \Throwable
    {
        return $this->throwable;
    }

    /**
     * @param \Throwable $throwable
     * @return $this
     */
    public function setThrowable(\Throwable $throwable) : self
    {
        $this->throwable = $throwable;
        return $this;
    }

    /**
     * 检查消息长度
     * @param $message
     * @throws MonitorException
     */
    public function checkLength($message)
    {
        if (is_string($message)) {
            $size = mb_strlen($message) / 1024;
            if ($size > 64) throw new MonitorException('消息大小不能超出64KB');
        }
    }

}