<?php
namespace qmmontitor\pojo;

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
     * 当前队列配置，来源于queue
     * @var array
     */
    private $nowQueueConfig = [];

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
     * @return array
     */
    public function getNowQueueConfig(): array
    {
        return $this->nowQueueConfig;
    }

    /**
     * @param array $nowQueueConfig
     * @return $this
     */
    public function setNowQueueConfig(array $nowQueueConfig): self
    {
        $this->nowQueueConfig = $nowQueueConfig;
        return $this;
    }
}