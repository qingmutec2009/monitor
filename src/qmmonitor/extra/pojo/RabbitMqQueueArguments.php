<?php
namespace qmmonitor\extra\pojo;

/**
 * rabbitmq队列参数
 * Class QueueArguments
 * @package app\mq\pojo
 */
class RabbitMqQueueArguments
{
    /**
     * rabbitmq中的交换机
     * @var string
     */
    private $exchange = '';

    /**
     * rabbitmq中的路由key
     * @var string
     */
    private $routeKey = '';

    /**
     * rabbitmq中的队列名称
     * @var string
     */
    private $queueName = '';

    /**
     * 是否生产确认
     * @var bool
     */
    private $producerConfirm = false;
    /**
     * 生产确认是是否阻塞，默认为阻塞
     * @var bool
     */
    private $noWait = false;

    public function __construct()
    {

    }

    /**
     * @return string
     */
    public function getExchange(): string
    {
        return $this->exchange;
    }

    /**
     * @param string $exchange
     * @return $this
     */
    public function setExchange(string $exchange): self
    {
        $this->exchange = $exchange;
        return $this;
    }

    /**
     * @return string
     */
    public function getRouteKey(): string
    {
        return $this->routeKey;
    }

    /**
     * @param string $routeKey
     * @return $this
     */
    public function setRouteKey(string $routeKey): self
    {
        $this->routeKey = $routeKey;
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
     * @return bool
     */
    public function getProducerConfirm(): bool
    {
        return $this->producerConfirm;
    }

    /**
     * @param bool $producerConfirm
     * @return $this
     */
    public function setProducerConfirm(bool $producerConfirm): self
    {
        $this->producerConfirm = $producerConfirm;
        return $this;
    }

    /**
     * @return bool
     */
    public function getNoWait(): bool
    {
        return $this->noWait;
    }

    /**
     * @param bool $noWait
     * @return $this
     */
    public function setNoWait(bool $noWait): self
    {
        $this->noWait = $noWait;
        return $this;
    }
}