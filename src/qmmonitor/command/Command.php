<?php
namespace qmmontitor\command;

use qmmontitor\core\Core;
use qmmontitor\extra\Color;
use qmmontitor\helper\PhpHelper;

class Command
{
    /**
     * appName
     * @var string
     */
    public $appName = 'monitor';

    public function check()
    {
        // Check requirements - Swoole
        if (extension_loaded('swoole') && defined('SWOOLE_VERSION')) {
            if (version_compare(SWOOLE_VERSION, '2.1.3', '<')) {
                exit(Color::error("ERROR: {$this->appName} requires [Swoole >= 4.5.3]."));
            }
        } else {
            exit(Color::error("ERROR: {$this->appName} was not installed."));
        }
        /*if (empty($this->method)) {
            exit($this->usage.PHP_EOL);
        }*/
        //以下会被其它框架接管，不一定会生效
        /*if ($this->option == '-h' || $this->option == '--help') {
            exit($this->usage.PHP_EOL);
        }*/
    }

    public function run(string $method,string $option = '',array $params = [])
    {
        $this->method = $method;
        $this->option = $option;
        $this->params = $params;
        //兼容opcache
        PhpHelper::opCacheClear();
        //检查
        $this->check();
        echo 'Server starting ...', PHP_EOL;
        //初始化配置文件
        Core::getInstance()->initialize();var_dump(44);die();
        //执行
        return Core::getInstance()->processManagerConsumerForMq();
    }
}