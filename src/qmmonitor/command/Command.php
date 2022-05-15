<?php
namespace qmmonitor\command;

use qmmonitor\core\ConfigurationManager;
use qmmonitor\core\Core;
use qmmonitor\extra\Color;
use qmmonitor\helper\FileHelper;
use qmmonitor\helper\PhpHelper;

class Command
{
    /**
     * @param array $config 配置文件
     * @param array $config
     */
    public function run(array $config = [])
    {
        //兼容opcache
        PhpHelper::opCacheClear();
        echo 'Server starting ...', PHP_EOL;
        ConfigurationManager::getInstance()->loadConfig($config);
        $this->directoryInit();
        $this->commandHandler();
    }


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
        $tempDir = ConfigurationManager::getInstance()->getConfig('temp_dir');
        if (empty($tempDir)) {
            //如果没有指定TEMP_DIR则会默认使用根目录下的Temp来作为临时目录
            $tempDir = MONITOR_ROOT . '/temp';
            ConfigurationManager::getInstance()->setConfig('temp_dir', $tempDir);
        } else {
            $tempDir = rtrim($tempDir, '/');
        }
        //如果临时目录不存在则会创建临时目录
        if (!is_dir($tempDir)) {
            FileHelper::createDirectory($tempDir);
        }
        //定义临时目录常量
        defined('MONITOR_TEMP_DIR') or define('MONITOR_TEMP_DIR', $tempDir);

        $logDir = ConfigurationManager::getInstance()->getConfig('log_dir');
        if (empty($logDir)) {
            $logDir = MONITOR_ROOT . '/log';
            ConfigurationManager::getInstance()->setConfig('log_dir', $logDir);
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
}