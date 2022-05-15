<?php
namespace qmmonitor;


use Phar;
use qmmonitor\core\Core;
use qmmonitor\extra\Color;
use qmmonitor\helper\PhpHelper;

defined('SWOOLE_VERSION') or define('SWOOLE_VERSION', intval(phpversion('swoole')));
defined('MONITOR_ROOT') or define('MONITOR_ROOT', realpath(getcwd()));

require_once __DIR__ . '/../../vendor/autoload.php';

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

class Bootstrap
{

    public function run()
    {
        if (!empty($configPath)) $this->configPath = $configPath;
        //兼容opcache
        PhpHelper::opCacheClear();
        //检查
        //$this->check();
        echo 'Server starting ...', PHP_EOL;
        //初始化配置文件
        Core::getInstance()->initialize();
        //执行
        return Core::getInstance()->processManagerConsumerForMq();
    }
}