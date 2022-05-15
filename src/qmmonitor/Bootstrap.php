<?php
namespace qmmonitor;

use qmmontitor\core\Core;
use qmmontitor\helper\PhpHelper;

class Bootstrap
{

    public function run(string $method,string $option = '',array $params = [])
    {
        $this->method = $method;
        $this->option = $option;
        $this->params = $params;
        //兼容opcache
        PhpHelper::opCacheClear();
        //检查
        //$this->check();
        echo 'Server starting ...', PHP_EOL;
        //初始化配置文件
        Core::getInstance()->initialize();var_dump(44);die();
        //执行
        return Core::getInstance()->processManagerConsumerForMq();
    }
}