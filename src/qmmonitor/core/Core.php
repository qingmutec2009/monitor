<?php
namespace qmmontitor\core;

use PhpAmqpLib\Message\AMQPMessage;
use qmmontitor\command\Singleton;

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

    public function initialize()
    {
        $this->sysDirectoryInit();
    }

    /**
     * 初始化系统的默认目录配置
     */
    private function sysDirectoryInit()
    {
        //创建临时目录    请以绝对路径，不然守护模式运行会有问题
        $tempDir = ConfigManager::getInstance()->getConfig('temp_dir');
        if (empty($tempDir)) {
            //如果没有指定TEMP_DIR则会默认使用根目录下的Temp来作为临时目录
            $tempDir = MONITOR_ROOT . '/temp';
            ConfigManager::getInstance()->setConfig('temp_dir', $tempDir);
        } else {
            $tempDir = rtrim($tempDir, '/');
        }
        //如果临时目录不存在则会创建临时目录
        if (!is_dir($tempDir)) {
            FileHelper::createDirectory($tempDir);
        }
        //定义临时目录常量
        defined('MONITOR_TEMP_DIR') or define('MONITOR_TEMP_DIR', $tempDir);

        $logDir = ConfigManager::getInstance()->getConfig('log_dir');
        if (empty($logDir)) {
            $logDir = MONITOR_ROOT . '/log';
            ConfigManager::getInstance()->setConfig('log_dir', $logDir);
        } else {
            $logDir = rtrim($logDir, '/');
        }
        if (!is_dir($logDir)) {
            FileHelper::createDirectory($logDir);
        }
        defined('MONITOR_LOG_DIR') or define('MONITOR_LOG_DIR', $logDir);

        // 设置默认文件目录值(如果自行指定了目录则优先使用指定的)
        /*if (!Config::getInstance()->getConf('MAIN_SERVER.SETTING.pid_file')) {
            Config::getInstance()->setConf('MAIN_SERVER.SETTING.pid_file', $tempDir . '/pid.pid');
        }
        if (!Config::getInstance()->getConf('MAIN_SERVER.SETTING.log_file')) {
            Config::getInstance()->setConf('MAIN_SERVER.SETTING.log_file', $logDir . '/swoole.log');
        }*/
        return $this;
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
        $amqpConfig = ConfigManager::getInstance()->getConfig('amqp');
        //获取任务配置
        $queuesConfig = ConfigManager::getInstance()->getConfig('queue');
        //创建进程管理器
        //ProcessManager::getInstance()->createProcess(SOCK_STREAM);
        ProcessManager::getInstance()->createProcess();
        foreach ($queuesConfig as $queueName => $nowQueueConfig) {
            ProcessManager::getInstance()->executeRabbitMq($enableCoroutine,$queueName,$amqpConfig,$nowQueueConfig);
        }
        ProcessManager::getInstance()->start();
    }


}