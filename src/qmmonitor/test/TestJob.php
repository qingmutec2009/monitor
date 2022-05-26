<?php
namespace qmmonitor\test;

use qmmonitor\extra\abstracts\AbstractJob;
use qmmonitor\extra\pojo\JobArguments;
use think\facade\Db;

/**
 * 测试工作任务
 * Class TestJob
 * @package qmmonitor\test
 */
class TestJob extends AbstractJob
{


    public function register($param)
    {
        $this->params = json_decode($this->params,true);
    }


    public function handle()
    {

        $queueName = $this->jobArguments->getQueueName();
        //echo "当前队列名称{$queueName}".PHP_EOL;
        //file_put_contents('consumer.txt',$queueName.'+'.date('Y-m-d H:i:s'));


        //手动确认
        $this->ack();
        //var_dump($this->params);
        //var_dump($this->jobArguments->getConfigurationManager()->getConfig('queue'));
        //var_dump($this->jobArguments->getChannel());
        //var_dump("更新完成");
    }
}