<?php
namespace qmmonitor\extra\pojo;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use qmmonitor\core\ConfigurationManager;

/**
 * 任务参数实体类
 * Class JobArguments
 * @package app\mq\pojo
 */
class JobArguments
{
    /**
     * 队列中取出来的消息
     * @var string
     */
    private $message = '';
    /**
     * 当前工作进程
     * @var int
     */
    private $workerId = 0;
    /**
     * 主进程id
     * @var int
     */
    private $pid = 0;
    /**
     * 队列名称
     * @var string
     */
    private $queueName = '';

    /**
     * AMQPChannel
     * @var null
     */
    private $channel = null;

    /**
     * AMQPMessage
     * @var null
     */
    private $AMQPmessage = null;

    /**
     * @var ConfigurationManager
     */
    private $configurationManager = null;

    /**
     * 当前运行的任务名称
     * @var string
     */
    private $jobName = '';

    /**
     * 附带的自定义参数
     * @var array
     */
    private $extendParams = [];

    /**
     * 进程名称
     * @var string
     */
    private $processName = '';

    /**
     * 当前重试次数值,从1开始
     * @var int
     */
    private $retryNum = 1;

    public function __construct()
    {

    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return int
     */
    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    /**
     * @param int $workerId
     * @return $this
     */
    public function setWorkerId(int $workerId): self
    {
        $this->workerId = $workerId;
        return $this;
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * @param int $pid
     * @return $this
     */
    public function setPid(int $pid): self
    {
        $this->pid = $pid;
        return $this;
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    /**
     * @param string $queueName
     * @return $this
     */
    public function setQueueName(string $queueName): self
    {
        $this->queueName = $queueName;
        return $this;
    }

    /**
     * @return AMQPChannel
     */
    public function getChannel() : AMQPChannel
    {
        return $this->channel;
    }

    /**
     * @param AMQPChannel $channel
     * @return $this
     */
    public function setChannel(AMQPChannel $channel): self
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * @return AMQPMessage
     */
    public function getAMQPmessage() : ?AMQPMessage
    {
        return $this->AMQPmessage;
    }

    /**
     * @param AMQPMessage $AMQPmessage
     * @return $this
     */
    public function setAMQPmessage(AMQPMessage $AMQPmessage): self
    {
        $this->AMQPmessage = $AMQPmessage;
        return $this;
    }

    /**
     * @return ConfigurationManager
     */
    public function getConfigurationManager(): ?ConfigurationManager
    {
        return $this->configuration;
    }

    /**
     * @param ConfigurationManager $configuration
     * @return $this
     */
    public function setConfigurationManager(?ConfigurationManager $configuration): self
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @return string
     */
    public function getJobName(): string
    {
        return $this->jobName;
    }

    /**
     * @param string $jobName
     * @return $this
     */
    public function setJobName(string $jobName): self
    {
        $this->jobName = $jobName;
        return $this;
    }

    /**
     * @return array
     */
    public function getExtendParams(): array
    {
        return $this->extendParams;
    }

    /**
     * @param array $extendParams
     * @return $this
     */
    public function setExtendParams(array $extendParams): self
    {
        $this->extendParams = $extendParams;
        return $this;
    }

    /**
     * @return string
     */
    public function getProcessName(): string
    {
        return $this->processName;
    }

    /**
     * @param string $processName
     * @return $this
     */
    public function setProcessName(string $processName): self
    {
        $this->processName = $processName;
        return $this;
    }

    /**
     * @return int
     */
    public function getRetryNum(): int
    {
        return $this->retryNum;
    }

    /**
     * @param int $retryNum
     * @return $this
     */
    public function setRetryNum(int $retryNum): self
    {
        $this->retryNum = $retryNum;
        return $this;
    }
}