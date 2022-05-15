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
}