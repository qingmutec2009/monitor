<?php


namespace qmmonitor\extra\abstracts;

use PhpAmqpLib\Message\AMQPMessage;
use qmmonitor\core\ConfigurationManager;
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
     * 当config中的queue_run_right_now参数=true时，此对象中只有消息本体和队列相关参数有效
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
        //初始化数据属性
        $this->initAttr($jobArguments);
        //注册、兼容旧版本
        $this->register($this->params);
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
     * 用于手动确认的方法
     * @param AMQPMessage|null $AMQPMessage
     * @return bool
     */
    protected function ack(?AMQPMessage $AMQPMessage = null) : bool
    {
        //如果是非异步模式则返回false
        $queueRunRightNow = $this->jobArguments->getConfigurationManager()->getConfig('queue_run_right_now');
        if ($queueRunRightNow) return false;
        if (empty($AMQPMessage)) $AMQPMessage = $this->jobArguments->getAMQPmessage();
        //获取当前队列是否是手动确认模式
        $queuesConfig = $this->jobArguments->getConfigurationManager()->getConfig('queue');
        $queueConfig = $queuesConfig[$this->jobArguments->getQueueName()];
        $autoAck = (bool)$queueConfig['auto_ack'] ?? true;
        //如果是自动确认则跳过,如果不是自动确认则会调用确认方法
        if (!$autoAck) {
            $amqpConfig = $this->jobArguments->getConfigurationManager()->getConfig('amqp');
            if (!$amqpConfig['no_ack']) {
                $AMQPMessage->ack();
                return true;
            }
        }
        return false;
    }

    /**
     * 注册参数
     * 注册、兼容旧版本 ，也可以当作初始化函数
     * @param $param
     * @return mixed
     */
    abstract public function register($param);


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