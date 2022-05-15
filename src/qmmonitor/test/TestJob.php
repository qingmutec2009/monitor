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
    /**
     * 参数
     * @var array
     */
    protected $params = '';

    /**
     * @var \qmmonitor\extra\pojo\JobArguments
     */
    protected $jobArguments;

    public function __costruct(?JobArguments $jobArguments)
    {
        $this->initAttr($jobArguments);
    }
    /**
     * 执行
     */
    public final function perform(?JobArguments $jobArguments = null)
    {
        $this->initAttr($jobArguments);
        //注册、兼容旧版本
        $this->register($this->params);
        //初始化
        $this->initialize();
        //执行
        $this->handle();
    }

    /**
     * 初始化属性
     * @param JobArguments|null $jobArguments
     */
    private function initAttr(?JobArguments $jobArguments)
    {
        if (!is_null($jobArguments)) {
            $this->params = $jobArguments->getMessage();
            $this->jobArguments = $jobArguments;
        }
    }

    /**
     * 框架日记
     *
     * @param $message
     * @param $type
     */
    protected function jobLog(string $message,$type)
    {
        $path   = MONITOR_LOG_DIR.date('Y-m-d').'-'.$type.'.log';
        $string = $message."\n";
        file_put_contents($path,$string,FILE_APPEND);
    }

    /**
     * 队列日志
     * @param string $message
     */
    protected function infoLog(string $message)
    {
        $path   = MONITOR_LOG_DIR.date('Y-m-d').$this->jobArguments->getQueueName().'.log';
        $string = date('Y-m-d H:i:s').' '.$message."\n";
        file_put_contents($path, $string, FILE_APPEND);
    }

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