<?php
namespace qmmontitor\helper;


use app\mo\console\Command;
use app\mq\config\ConfigManager;

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
        $tempDir = ConfigManager::getInstance()->getConfig('temp_dir');
        $pidFile = $tempDir.'/'.Command::getInstance()->appName.'.pid';
        return $pidFile;
    }

    /**
     * PhpHelper::kill("php-work");
     * @param string $str
     */
    public static function kill(string $str)
    {
        $command = "kill -s 9  `ps -aux | grep {$str} | awk '{print $2}'`";//php-work
        if (self::isLinux() && self::isCli()) {
            exec($command,$output,$resultCode);
        }
    }
}