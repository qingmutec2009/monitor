<?php


namespace qmmonitor\extra\abstracts;

use qmmonitor\extra\pojo\JobArguments;

/**
 * 工作基类
 * Class AbstractJob
 * @package qmmonitor\extra\abstracts
 */
abstract class AbstractJob
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
    /**
     * 注册参数
     * 注册、兼容旧版本
     * @param $param
     * @return mixed
     */
    abstract public function register($param);

    /**
     * 初始化
     * @return mixed
     */
    abstract public function initialize();

    /**
     * 任务入口
     * 如果没有自定义操作,不要对异常进行捕获
     * 队列本身的异常扑捉更加完善
     * try后的异常将不会在队列的失败列表中
     *
     * @return mixed
     */
    abstract public function handle();
}