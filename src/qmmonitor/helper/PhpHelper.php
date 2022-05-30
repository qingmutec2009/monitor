<?php
namespace qmmonitor\helper;


use app\mo\console\Command;
use app\mq\config\ConfigManager;
use qmmonitor\core\ConfigurationManager;
use qmmonitor\core\RabbitMqManager;

class PhpHelper
{

    public static function opCacheClear()
    {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * is Cli
     *
     * @return  boolean
     */
    public static function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * 是否是mac环境
     *
     * @return bool
     */
    public static function isMac(): bool
    {
        return php_uname('s') === 'Darwin';
    }

    /**
     * 是否是linux
     * @return bool
     */
    public static function isLinux()
    {
        return php_uname('s') === 'Linux';
    }

    /**
     * 获取pid文件
     * @return string
     */
    public static function getPidFile() : string
    {
        $tempDir = ConfigurationManager::getInstance()->getConfig('temp_dir');
        $pidFile = $tempDir.'/'.Command::getInstance()->appName.'.pid';
        return $pidFile;
    }

    /**
     * PhpHelper::kill("php-work");
     * @param string $str
     * @param int $signo
     */
    public static function killAll(string $str = '',$signo = 15)
    {
        if (empty($str)) $str = self::getProcessNameSearchName(\qmmonitor\command\Command::$projectName);
        $command = "kill -s {$signo}  `ps -aux | grep {$str} | awk '{print $2}'`";
        if (self::isLinux() && self::isCli()) {
            exec($command,$output,$resultCode);
        }

    }

    /**
     *
     * @param $pid
     */
    public static function kill($pid,$signo = 15)
    {
        $command = "kill -{$signo} {$pid}";
        if (self::isLinux() && self::isCli()) {
            exec($command,$output,$resultCode);
        }
    }

    /**
     * 获取id
     * @param string $item
     * @return int
     */
    public static function getPidFromOutput(string $item)
    {
        $item = trim($item);
        $items = explode(" ",$item);
        return (int)$items[0] ?? 0;
    }

    /**
     * 转化配置中的bool值
     * @param $val
     * @return bool
     */
    public static function formatConfigurationBool($val) : bool
    {
        if (is_string($val)) {
            return $val === 'true' ? true : false;
        } elseif (is_numeric($val)) {
            return (bool)$val;
        }
        return false;
    }

    /**
     * 获取主进程id
     * @return int
     */
    public static function getPid()
    {
        return posix_getpid();
    }

    /**
     * 获取工作进程列表
     * @param string $findStr
     * @return array
     */
    public static function getWorkList(string $findStr = '') : array
    {
        //如果未传递则会默认取当前应用名称
        if (empty($findStr)) $findStr = self::getProcessNameSearchName(\qmmonitor\command\Command::$projectName);
        $result = [];
        if (self::isLinux() && self::isCli()) {
            exec("ps -A -opid -oargs | grep {$findStr}",$output);
            if (!empty($output) && is_array($output)) {
                foreach ($output as $item) {
                    if (strpos($item,'php-work-') !== false) {
                        array_push($result,$item);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 获取进程名称前缀
     * @param string $projectName
     * @return string
     */
    public static function getProcessNameSearchName(string $projectName) : string
    {
        $applicationName = \qmmonitor\command\Command::APPLICATION_NAME;
        return  "{$applicationName}-{$projectName}";
    }

}