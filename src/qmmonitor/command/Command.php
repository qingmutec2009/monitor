<?php
namespace qmmonitor\command;

use qmmonitor\core\ConfigurationManager;
use qmmonitor\core\Core;
use qmmonitor\extra\Color;
use qmmonitor\extra\pojo\RabbitMqQueueArguments;
use qmmonitor\helper\FileHelper;
use qmmonitor\helper\PhpHelper;

/**
 * 命令处理类
 * Class Command
 * @package qmmonitor\command
 */
class Command
{
    public function __construct()
    {
        defined('SWOOLE_VERSION') or define('SWOOLE_VERSION', intval(phpversion('swoole')));
        defined('MONITOR_ROOT') or define('MONITOR_ROOT', realpath(getcwd()));
    }

    /**
     * @param array $config 配置文件
     * @param array $config
     */
    public function run(array $config = [])
    {
        $this->checkEnvironment();
        //兼容opcache
        PhpHelper::opCacheClear();
        ConfigurationManager::getInstance()->loadConfig($config);
        $ack = ConfigurationManager::getInstance()->getConfig('amqp.no_ack');
        if ($ack) {
            exit(Color::error('当前版本下必须是消息确认模式，暂时不支持不使用ack'));
        }
        $this->directoryInit();
        echo 'monitor starting ...', PHP_EOL;
        $this->commandHandler();
    }

    /**
     * 命令执行
     */
    public function commandHandler()
    {
        Core::getInstance()->processManagerConsumerForMq();
    }

    /**
     * 初始化系统的默认目录配置
     */
    private function directoryInit()
    {
        //创建临时目录    请以绝对路径，不然守护模式运行会有问题
        /*$tempDir = ConfigurationManager::getInstance()->getConfig('temp_dir');
        if (empty($tempDir)) {
            //如果没有指定TEMP_DIR则会默认使用根目录下的Temp来作为临时目录
            $tempDir = MONITOR_ROOT . '/temp';
            ConfigurationManager::getInstance()->setConfig('temp_dir', $tempDir);
        } else {
            $tempDir = rtrim($tempDir, '/');
        }*/
        //如果临时目录不存在则会创建临时目录
        /*if (!is_dir($tempDir)) {
            FileHelper::createDirectory($tempDir);
        }*/
        //定义临时目录常量
        //defined('MONITOR_TEMP_DIR') or define('MONITOR_TEMP_DIR', $tempDir);

        /*$logDir = ConfigurationManager::getInstance()->getConfig('log_dir');
        if (empty($logDir)) {
            $logDir = MONITOR_ROOT . '/log';
            ConfigurationManager::getInstance()->setConfig('log_dir', $logDir);
        } else {
            $logDir = rtrim($logDir, '/');
        }
        if (!is_dir($logDir)) {
            FileHelper::createDirectory($logDir);
        }
        defined('MONITOR_LOG_DIR') or define('MONITOR_LOG_DIR', $logDir);*/

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
     * 检查环境
     */
    private function checkEnvironment()
    {
        if (version_compare(PHP_VERSION, '7.1', '<')) {
            exit(Color::error("ERROR: SMProxy requires [PHP >= 7.1]."));
        }
        // Check requirements - Swoole
        if (extension_loaded('swoole') && defined('SWOOLE_VERSION')) {
            if (version_compare(SWOOLE_VERSION, '4.5.3', '<')) {
                exit(Color::error("ERROR: qmmonitor requires [Swoole >= 4.5.3]."));
            }
        } else {
            //todo 临时注释
            //exit(Color::error("ERROR: swoole was not installed."));
        }

        if (extension_loaded('xdebug')) {
            exit(Color::error("ERROR: XDebug has been enabled, which conflicts with qmmonitor."));
        }
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
        ConfigurationManager::getInstance()->loadConfig($config);
        return Core::getInstance()->put($message,$rabbitMqQueueArguments,$config);
    }
}