<?php
namespace qmmonitor\helper;


use app\mo\console\Command;
use app\mq\config\ConfigManager;
use qmmonitor\core\ConfigurationManager;
use qmmonitor\core\RabbitMqManager;
use qmmonitor\extra\Color;

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
     * @param string $appName
     * @param int $signo
     */
    public static function killAll(string $appName = '',$signo = 15)
    {
        if (empty($appName)) $appName = self::getProcessNameSearchName(\qmmonitor\command\Command::$projectName);
        $findStr = "php-work-{$appName}";
        $command = "kill -s {$signo}  `ps -aux | grep {$findStr} | awk '{print $2}'`";
        if (self::isLinux() && self::isCli()) {
            exec($command,$output,$resultCode);
        }
    }

    /**
     * 根据活动中决定是否kill
     */
    public static function killByActivity()
    {
        $maxWait = (int)ConfigurationManager::getInstance()->getConfig('reload_max_wait_time');
        $waitingStr = '等待中,还有进程在执行中.';
        //正式准备删除
        for ($i = 0; $i < $maxWait; $i ++) {
            $list = PhpHelper::getWorkList();
            foreach ($list as $item) {
                if (strpos($item,'activity') === false) {
                    //只要不是活动进程则一律停止
                    echo Color::notice("进程:{$item}已停止".PHP_EOL);
                    $workId = PhpHelper::getPidFromOutput($item);
                    PhpHelper::kill($workId,9);
                }
            }
            //重新再拉一次
            $list = PhpHelper::getWorkList();
            if (empty($list)) break;
            sleep(1);
            echo Color::notice($waitingStr.str_repeat('.',$i + 1).PHP_EOL);
        }
        //超过15S依然无法完全kill完全的话则人工介入
        if (!empty(PhpHelper::getWorkList())) {
            exit(Color::notice("application ".\qmmonitor\command\Command::APPLICATION_NAME." reload failed.....").PHP_EOL);
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

    /**
     * 删除主进程
     */
    public static function killMaster()
    {
        $file = ConfigurationManager::getInstance()->getConfig('pid_file');
        if (!is_file($file)) {
            echo (Color::warning("pid文件不存在，可能被删除").PHP_EOL);
        } else {
            $pid = file_get_contents($file);
            PhpHelper::kill($pid,9);
            unlink($file);
        }
    }

    /**
     * 关闭时输出信息
     */
    public static function closeInfoOutput()
    {
        $list = PhpHelper::getWorkList();
        foreach ($list as $item) {
            echo Color::notice("当前正在关闭的进程信息:{$item}".PHP_EOL);
        }
    }

    /**
     * 格式化大小
     * @param $bytes
     * @param int $precision
     * @return string
     */
    public static function formatBytes($bytes, $precision = 2) {
        $units = array("b", "kb", "mb", "gb", "tb");
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . " " . $units[$pow];
    }

    /**
     * Converts a human readable file size value to a number of bytes that it
     * represents. Supports the following modifiers: K, M, G and T.
     * Invalid input is returned unchanged.
     *
     * Example:
     * <code>
     * $config->formatToByte(10);          // 10
     * $config->formatToByte('10b');       // 10
     * $config->formatToByte('10k');       // 10240
     * $config->formatToByte('10K');       // 10240
     * $config->formatToByte('10kb');      // 10240
     * $config->formatToByte('10Kb');      // 10240
     * // and even
     * $config->formatToByte('   10 KB '); // 10240
     * </code>
     *
     * @param number|string $value
     * @return number
     */
    public static function formatToByte($value) {
        return preg_replace_callback('/^\s*(\d+)\s*(?:([kmgt]?)b?)?\s*$/i', function ($m) {
            switch (strtolower($m[2])) {
                case 't': $m[1] *= 1024;
                case 'g': $m[1] *= 1024;
                case 'm': $m[1] *= 1024;
                case 'k': $m[1] *= 1024;
            }
            return $m[1];
        }, $value);
    }

    /**
     * $start|$end的获取方式是:microtime(true);
     * 获取时间差，毫秒级
     * @param $start
     * @param $end
     * @param string $unitv 时间单位，用于格式化
     */
    public static function subtraction($start,$end,$unit = 'ms')
    {
        return !empty($unit) ? (($end - $start) * 1000) . $unit : (($end - $start) * 1000);
    }

    /**
     * 获取记录
     * @deprecated
     * @return array
     */
    public static function getRecord() : array
    {
        $recordFile = self::getRecordFile();
        if (is_file($recordFile)) {
            $json = file_get_contents($recordFile);
            $content = !empty($json) ? json_decode($json,true) : self::getRecordContent();
        } else {
            $content = self::getRecordContent();
        }
        return $content;
    }

    /**
     * 获取记录文件
     * @deprecated
     * @return string
     */
    public static function getRecordFile() : string
    {
        $tempDir = ConfigurationManager::getInstance()->getConfig('temp_dir');
        $recordFile = $tempDir . '/' . 'record.log';
        return $recordFile;
    }

    /**
     * 获取记录内容
     * @deprecated
     * @return array
     */
    public static function getRecordContent() : array
    {
        $content = [
            'record_time' => time(),
        ];
        return $content;
    }

    /**
     * 如果record_time=记录时间+reconnection_interval=间隔时间>当前时间则需要重置记录
     * @deprecated
     * @param array $content 记录内容
     * @param int $reConnectionInterval 重连间隔
     * @return bool
     */
    public static function record(array $content,int $reConnectionInterval) : bool
    {
        //是否已经超时
        $nowtime = time();
        $isExpire = $nowtime > ($content['record_time'] + $reConnectionInterval) ? true : false;
        if ($isExpire) {
            //重新记录
            $content['record_time'] = $nowtime;
            $recordFile = self::getRecordFile();
            $recordStr = json_encode($content,JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE);
            file_put_contents($recordFile,$recordStr,LOCK_EX);
            //如果是debug模式则输出
            if (ConfigurationManager::getInstance()->getConfig('debug')) {
                echo Color::info("当前已开启重连间隔时间配置并监测到已过期，已记录了本地重置时间{$recordStr}".PHP_EOL);
            }
        }
        return $isExpire;
    }
}