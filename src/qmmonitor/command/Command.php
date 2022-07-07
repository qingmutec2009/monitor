<?php
namespace qmmonitor\command;

use qmmonitor\core\ConfigurationManager;
use qmmonitor\core\Core;
use qmmonitor\extra\Color;
use qmmonitor\extra\pojo\RabbitMqQueueArguments;
use qmmonitor\extra\traits\Singleton;
use qmmonitor\helper\FileHelper;
use qmmonitor\helper\PhpHelper;

/**
 * 命令处理类
 * Class Command
 * @package qmmonitor\command
 */
class Command
{

    const APPLICATION_NAME = 'monitor';

    /**
     * 必须传入当前使用项目的名称，会被用作标识
     * @var string
     */
    public static $projectName = '';

    public function __construct(string $projectName)
    {
        defined('SWOOLE_VERSION') or define('SWOOLE_VERSION', intval(phpversion('swoole')));
        defined('MONITOR_ROOT') or define('MONITOR_ROOT', realpath(getcwd()));
        self::$projectName = $projectName;
    }

    /**
     * @param array $config 配置文件
     * @param array $config
     */
    public function start(array $config = [])
    {
        //是否有启动相关进程
        $list =PhpHelper::getWorkList();
        if (!empty($list)) exit(Color::error('不能重复启动'));
        $this->checkEnvironment();
        //兼容opcache
        PhpHelper::opCacheClear();
        ConfigurationManager::getInstance()->loadConfig($config);
        $ack = ConfigurationManager::getInstance()->getConfig('amqp.no_ack');
        if ($ack) {
            exit(Color::error('当前版本下必须是消息确认模式，暂时不支持不使用ack'));
        }
        $this->directoryInit();
        echo Color::notice(self::APPLICATION_NAME.' starting ...').PHP_EOL;
        $this->commandHandler();
    }

    /**
     * 命令执行
     */
    public function commandHandler()
    {
        $pid = PhpHelper::getPid();
        file_put_contents(ConfigurationManager::getInstance()->getConfig('pid_file'),$pid);
        Core::getInstance()->processManagerConsumerForMq();
    }

    /**
     * 初始化系统的默认目录配置
     */
    private function directoryInit()
    {
        //创建临时目录
        $tempDir = ConfigurationManager::getInstance()->getConfig('temp_dir',MONITOR_ROOT.'/temp');
        $tempDir = rtrim($tempDir, '/');
        //如果没有指定TEMP_DIR则会默认使用根目录下的Temp来作为临时目录
        ConfigurationManager::getInstance()->setConfig('temp_dir', $tempDir);
        //如果临时目录不存在则会创建临时目录
        if (!is_dir($tempDir)) {
            FileHelper::createDirectory($tempDir);
        }
        //定义临时目录常量
        defined('MONITOR_TEMP_DIR') or define('MONITOR_TEMP_DIR', $tempDir);

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
        if (empty(ConfigurationManager::getInstance()->getConfig('pid_file'))) {
            ConfigurationManager::getInstance()->setConfig('pid_file', $tempDir . '/pid.pid');
        }
        /*if (!Config::getInstance()->getConf('MAIN_SERVER.SETTING.log_file')) {
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

    /**
     * 强制停止
     * @param array $config
     * @throws \Exception
     */
    public function stop(array $config = [])
    {
        ConfigurationManager::getInstance()->loadConfig($config);
        $this->directoryInit();
        $file = ConfigurationManager::getInstance()->getConfig('pid_file');
        //处理主进程
        if (!is_file($file)) {
            echo (Color::error("pid文件不存在，可能被删除").PHP_EOL);
        } else {
            $pid = file_get_contents($file);
            PhpHelper::kill($pid,9);
            unlink($file);
        }
        $list = PhpHelper::getWorkList();
        if (!empty($list)) {
            PhpHelper::killAll('',9);
        }
    }
    /**
     * 安全的停止进程
     * @param array $config
     * @throws \Exception
     */
    public function reload(array $config = [])
    {
        ConfigurationManager::getInstance()->loadConfig($config);
        $this->directoryInit();
        $file = ConfigurationManager::getInstance()->getConfig('pid_file');
        //处理主进程
        if (!is_file($file)) {
            echo (Color::error("pid文件不存在，可能被删除").PHP_EOL);
        } else {
            $pid = file_get_contents($file);
            PhpHelper::kill($pid,9);
            unlink($file);
        }
        //处理子进程
        $list = PhpHelper::getWorkList();
        foreach ($list as $item) {
            echo Color::notice("当前正在关闭的进程信息:{$item}".PHP_EOL);
        }
        //初次发送信号进程进行准备
        PhpHelper::killAll();
        sleep(3);
        $maxWait = (int)ConfigurationManager::getInstance()->getConfig('reload_max_wait_time');
        //正式准备删除
        for ($i = 0; $i < $maxWait; $i ++) {
            $list = PhpHelper::getWorkList();
            foreach ($list as $item) {
                if (strpos($item,'activity') === false) {
                    //只要不是活动进程则一律停止
                    $workId = PhpHelper::getPidFromOutput($item);
                    PhpHelper::kill($workId,9);
                }
            }
            //重新再拉一次
            $list = PhpHelper::getWorkList();
            if (empty($list)) break;
            sleep(1);
        }
        //超过15S依然无法完全kill完全的话则人工介入
        if (!empty(PhpHelper::getWorkList())) {
            exit(Color::notice("application ".self::APPLICATION_NAME." reload failed.....").PHP_EOL);
        }
        echo (Color::notice("application ".self::APPLICATION_NAME." already stopped.....").PHP_EOL);
        echo Color::notice("starting").PHP_EOL;
        //重启
        $this->start($config);

    }

    /**
     * 重启
     * @param array $config
     * @throws \Exception
     */
    public function restart(array $config = [])
    {
        echo Color::notice("closing").PHP_EOL;
        $this->stop($config);
        echo Color::notice("starting").PHP_EOL;
        $this->start($config);
        echo Color::notice("application ".self::APPLICATION_NAME." already started.....").PHP_EOL;
    }

    /**
     * 列举出当前任务清单
     * @param string $findStr
     * @return array
     */
    public function list(string $findStr = '') : array
    {
        return PhpHelper::getWorkList($findStr);
    }

    /**
     * @return string
     */
    public function getProjectName(): string
    {
        return $this->projectName;
    }

    /**
     * @param string $projectName
     */
    public function setProjectName(string $projectName): void
    {
        $this->projectName = $projectName;
    }
}