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
        // TODO: Implement register() method.
    }

    public function initialize()
    {
        $this->params = json_decode($this->params,true);
    }

    public function handle()
    {

        /*$update = [
            'worker_id' => $this->jobArguments->getWorkerId(),
            'queue_name'    => $this->jobArguments->getQueueName(),
        ];
        Db::table('xfhz_record')->where('record_id',$this->params['record_id'])
            ->update($update);*/
        var_dump($this->params);
        //var_dump("更新完成");
    }
}