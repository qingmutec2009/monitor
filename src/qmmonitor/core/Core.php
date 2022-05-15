<?php
namespace qmmonitor\core;

use qmmonitor\extra\pojo\JobArguments;
use qmmonitor\extra\traits\Singleton;
use qmmonitor\helper\FileHelper;
use qmmonitor\process\ProcessManager;

/**
 * Class Core
 * @package app\object\core
 */
class Core
{
    use Singleton;

    public function __construct()
    {
    }

    /**
     * 执行脚本
     * @param array $nowQueueConfig
     * @param $arguments 当前参数信息
     */
    public function runJob(JobArguments $jobArguments)
    {
        $nowQueueConfig = $jobArguments->getNowQueueConfig();
        $jobs = $nowQueueConfig['job'];
        foreach ($jobs as $job) {
            //依次执行相关任务
            $jobObject = new $job();
            $jobObject->perform($jobArguments);
        }
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
        //创建进程管理器
        //ProcessManager::getInstance()->createProcess(SOCK_STREAM);
        ProcessManager::getInstance()->createProcess();
        foreach ($queuesConfig as $queueName => $nowQueueConfig) {
            ProcessManager::getInstance()->executeRabbitMq($enableCoroutine,$queueName,$amqpConfig,$nowQueueConfig);
        }
        ProcessManager::getInstance()->start();
    }


}