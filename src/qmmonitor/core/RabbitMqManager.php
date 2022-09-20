<?php
namespace qmmonitor\core;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use qmmonitor\command\Command;
use qmmonitor\exception\MonitorException;
use qmmonitor\extra\Color;
use qmmonitor\extra\pojo\JobArguments;
use qmmonitor\extra\pojo\RabbitMqQueueArguments;
use qmmonitor\extra\traits\Singleton;
use qmmonitor\helper\PhpHelper;
use qmmonitor\process\ProcessManager;
use Swoole\Coroutine;
use Swoole\Process;

/**
 *
 * Class RabbitMqComponent
 * @package app\mq\component
 */
class RabbitMqManager
{
    use Singleton;

    private $host = '127.0.0.1';
    private $port = 5672;
    private $user = '';
    private $password = '';
    private $virtual = '/';
    //发布配置
    private $insist = false;
    private $loginMethod = 'AMQPLAIN';
    private $loginResponse = null;
    private $locale = 'en_US';
    private $context = null;
    private $channelRpcTimeout = 0.0;
    private $sslProtocol = null;
    /**
     * 连接超时时间
     * @var int|mixed
     */
    private $connectionTimeout = 10.0;
    /**
     * 读写超时时间
     * @var int
     */
    private $rwTimeout = 10.0;
    /**
     * 让连接保持住
     * @var bool
     */
    private $keepAlive = false;
    /**
     * 进行心跳检测,如果超过这个时间没有收到心跳，客户端就会自动发起重连。单位秒
     * @var int
     */
    private $heartBeat = 0;

    //以下是消费配置
    private $consumerTag = '';
    private $noLocal = false;
    private $noAck= false;
    private $exclusive = false;
    private $noWait = false;
    private $ticket = null;
    private $arguments = [];

    /**
     * @var \PhpAmqpLib\Connection\AMQPStreamConnection
     */
    private $connection = null;
    /**
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    private $channel = null;



    public function __construct(array $config = [])
    {
        //基础
        $this->host = empty($config['host']) ? $this->host : $config['host'];
        $this->port = empty($config['port']) ? $this->port : (int)$config['port'];
        $this->user = empty($config['user']) ? $this->user : $config['user'];
        $this->password = empty($config['password']) ? $this->password : $config['password'];
        $this->virtual = empty($config['virtual']) ? $this->virtual : $config['virtual'];
        //发布配置
        $this->connectionTimeout = empty($config['connection_timeout']) ? $this->connectionTimeout : (float)$config['connection_timeout'];
        $this->rwTimeout = empty($config['read_write_timeout']) ? $this->rwTimeout : $config['read_write_timeout'];
        $this->keepAlive = empty($config['keep_alive']) ? $this->keepAlive : $config['keep_alive'];
        $this->insist = empty($config['insist']) ? $this->insist : $config['insist'];
        $this->heartBeat = empty($config['heart_beat']) ? $this->heartBeat : (int)$config['heart_beat'];
        $this->loginMethod = empty($config['login_method']) ? $this->loginMethod : $config['login_method'];
        $this->loginResponse = empty($config['login_response']) ? $this->loginResponse : $config['login_response'];
        $this->locale = empty($config['locale']) ? $this->locale : $config['locale'];
        $this->context = empty($config['context']) ? $this->context : $config['context'];
        $this->channelRpcTimeout = empty($config['channel_rpc_timeout']) ? $this->channelRpcTimeout : $config['channel_rpc_timeout'];
        $this->sslProtocol = empty($config['ssl_protocol']) ? $this->sslProtocol : $config['ssl_protocol'];
        //消费配置
        $this->consumerTag = empty($config['consumer_tag']) ? $this->consumerTag : $config['consumer_tag'];
        $this->noLocal = empty($config['no_local']) ? $this->noLocal : $config['no_local'];
        $this->noAck = empty($config['no_ack']) ? $this->noAck : $config['no_ack'];
        $this->exclusive = empty($config['exclusive']) ? $this->exclusive : $config['exclusive'];
        $this->noWait = empty($config['nowait']) ? $this->noWait : $config['nowait'];
        $this->ticket = empty($config['ticket']) ? $this->ticket : $config['ticket'];
        $this->arguments = empty($config['arguments']) ? $this->arguments : $config['arguments'];
        //初始化连接
        $this->getChannel();
        //是否自动完成交换机、队列及绑定
        $this->autoCreate();
    }

    /**
     * 获取连接对象
     * @return AMQPStreamConnection
     */
    public function getConnection()
    {
        if (empty($this->connection)) {
            $this->connection = new AMQPStreamConnection(
                $this->host,$this->port,$this->user,$this->password,$this->virtual,
                $this->insist,$this->loginMethod,$this->loginResponse,$this->locale,
                $this->connectionTimeout,$this->rwTimeout,$this->context,$this->keepAlive,$this->heartBeat,
                $this->channelRpcTimeout,$this->sslProtocol
            );
        }
        /*$connection = AMQPStreamConnection::create_connection([
            ['host' => HOST1, 'port' => PORT, 'user' => USER, 'password' => PASS, 'vhost' => VHOST],
            ['host' => HOST2, 'port' => PORT, 'user' => USER, 'password' => PASS, 'vhost' => VHOST]
        ],
            $options);*/
        return $this->connection;
    }

    /**
     * 获取渠道对象
     * @return \PhpAmqpLib\Channel\AbstractChannel|AMQPChannel
     */
    public function getChannel()
    {
        if (empty($this->channel)) {
            $this->getConnection();
            $this->channel = $this->qos($this->connection->channel());
        }
        return $this->channel;
    }

    /**
     * 关闭连接
     */
    public function close()
    {
        //关闭连接
        try {
            $this->channel->close();
            $this->connection->close();
        } catch (\Throwable $th) {
            echo (date('Y-m-d H:i:s') . ' - ' . 'rabbitmq消费等待消息超时关闭连接出错：' . $th->getMessage() . PHP_EOL . $th);
        }
    }

    /**
     * 设置qos
     * @param AMQPChannel|null $channel
     * @param $prefetchCount int
     * @param $prefetchCount
     * @param $aGlobal
     * @return AMQPChannel
     */
    public function qos(? AMQPChannel $channel = null,$prefetchCount = 1,$aGlobal = null) : AMQPChannel
    {
        if (empty($channel)) $channel = $this->getChannel();
        //设置prefetch_count=1。这样是告诉RabbitMQ，再同一时刻，不要发送超过1条消息给一个工作者（worker），即按消费能力分发
        //直到它已经处理了上一条消息并且作出了响应。这样，RabbitMQ就会把消息分发给下一个空闲的工作者（worker），轮询、负载均衡配置
        $channel->basic_qos(null, $prefetchCount, $aGlobal);
        $this->channel = $channel;
        return $channel;
    }

    /**
     * rabbitmq消费
     * @param string $queueName
     * @param $callBack
     * @param array $nowQueueConfig
     */
    public function consumer(string $queueName,\Closure $callBack, array $nowQueueConfig)
    {
        //初始化channel
        $consumerTag = $this->initChannelBasic($queueName, $callBack);
        #监听消息
        while(count($this->getChannel()->callbacks)) {
            try {
                //$timeout秒没有队列数据推送，就重新连接，防止服务端断开连接后，进程盲目等待
                $this->channel->wait(null, false, $nowQueueConfig['timeout']);
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                //需要重连
                if (ConfigurationManager::getInstance()->getConfig('debug')) {
                    $exceptionMsg = $e->getMessage();
                    echo Color::info("异常:{$exceptionMsg},正在重连.....".PHP_EOL);
                }
                $this->reconnect();
                //重新初始化channel
                $this->initChannelBasic($queueName, $callBack);
            }
        }
    }

    /**
     * 初始化 channel callbacks
     * @param string $queueName 队列名称
     * @param \Closure $callBack 回调函数
     */
    protected function initChannelBasic(string $queueName,\Closure $callBack) : string
    {
        return $this->channel->basic_consume(
            $queueName,
            RabbitMqManager::getInstance()->getConsumerTag(),
            RabbitMqManager::getInstance()->getNoLocal(),
            RabbitMqManager::getInstance()->getNoAck(),
            RabbitMqManager::getInstance()->getExclusive(),
            RabbitMqManager::getInstance()->getNoWait(),
            $callBack
        );
    }

    /**
     * 回调
     * @param AMQPChannel $channel 渠道
     * @param array $nowQueueConfig 当前队列配置
     * @param string $queueName 队列名称
     * @param $workerId 当前工作id 0、1、2、3、4这样
     * @param int $pid 当前父级工作进程id
     * @return \Closure
     */
    public function consumerCallBack(array $nowQueueConfig,string $queueName,int $workerId,$pid = 0,$processName = '') : \Closure
    {
        return  function (AMQPMessage $msg) use ($nowQueueConfig,$queueName,$workerId,$pid,$processName){
            if (!ProcessManager::$isRunning) {
                //echo "队列名称{$queueName}：监测到isRunning为false了，即将设置进程为stopped".PHP_EOL;
                //只要此属性发生变化为false,将停止一切消息行为,此处拦截将不会进入到业务代码中去。ACK机制也能够确保消息不丢失。
                $processName = ProcessManager::getInstance()->getProcessName($queueName,$workerId,Command::$projectName,'stopped');
                ProcessManager::getInstance()->setProcessName($processName);
                return '';
            }
            //说明是活动进程
            $processName = ProcessManager::getInstance()->getProcessName($queueName,$workerId,Command::$projectName,'activity');
            ProcessManager::getInstance()->setProcessName($processName);
            //处理当前整合信息
            $jobArguments = new JobArguments();
            $jobArguments->setMessage($msg->body);
            $jobArguments->setWorkerId($workerId);
            $jobArguments->setPid($workerId);
            $jobArguments->setQueueName($queueName);
            $jobArguments->setChannel($this->getChannel());
            $jobArguments->setAMQPmessage($msg);
            $jobArguments->setConfigurationManager(ConfigurationManager::getInstance());
            $jobArguments->setProcessName($processName);
            $isSuccess = true;
            //重试次数，根据配置而定默认=3
            $retry = ConfigurationManager::getInstance()->getConfig('retry');
            for ($i = 0;$i < $retry; $i ++) {
                //设置当前属于重试的第几次，以1为基准。
                $jobArguments->setRetryNum($i + 1);
                //处理业务逻辑
                if ($isSuccess = Core::getInstance()->runJob($jobArguments,$nowQueueConfig)) {
                    break;
                }
            }
            if (!$isSuccess) {
                echo Color::warning($processName."重试{$retry}次异常，即将调起配置中的回调".PHP_EOL);
                //如果重试结果依然是失败的，则会调起
                $exceptionClosure = ConfigurationManager::getInstance()->getConfig('exception_closure');
                $throwable = Core::getInstance()->getThrowable();
                $exceptionClosure($throwable,$msg,$nowQueueConfig);
            }
            //消息确认
            $autoAck = $nowQueueConfig['auto_ack'] ?? false;
            if ($autoAck && !$this->getNoAck()) {
                if (ConfigurationManager::getInstance()->getConfig('debug')) {
                    echo Color::info($processName."当前任务={$processName},当前消息={$jobArguments->getMessage()}即将自动确认".PHP_EOL);
                }
                $msg->ack();
            }
            //只要是走完一个流程，将重置进程名为已停止
            //echo "队列名称{$queueName}：队列流程已完成，即将设置进程为done".PHP_EOL;
            $processName = ProcessManager::getInstance()->getProcessName($queueName,$workerId,Command::$projectName,'done');
            ProcessManager::getInstance()->setProcessName($processName);
        };
    }

    /**
     * 重连
     * @return \PhpAmqpLib\Channel\AbstractChannel|AMQPChannel
     */
    public function reConnect()
    {
        //将连接对象重置为null,然后再重新连接
        $this->close();
        $this->connection = null;
        $this->channel = null;
        return $this->getChannel();
    }

    /**
     * 写入队列 todo 本方法可以通过interface或abstract来约束名称
     * @param mixed $message
     * @param RabbitMqQueueArguments $rabbitMqQueueArguments
     */
    public function put($message,RabbitMqQueueArguments $rabbitMqQueueArguments)
    {
        //获取messageId
        $messageId = $this->getMessageId($rabbitMqQueueArguments,$message);
        $options = $this->getPutOptions($messageId);
        //投入到rabbitMq
        $messageBody = $this->transformAMQPMessage($message,$options);
        if ($rabbitMqQueueArguments->getProducerConfirm()) {
            $this->getChannel()->confirm_select($rabbitMqQueueArguments->getNoWait());
        }
        $this->getChannel()->basic_publish($messageBody,$rabbitMqQueueArguments->getExchange(),$rabbitMqQueueArguments->getRouteKey());
        return true;
    }

    /**
     * 根据配置文件初始化交换机及队列及绑定
     */
    private function autoCreate()
    {
        $exchanges = ConfigurationManager::getInstance()->getConfig('exchanges');
        //获取自动创建的队列的交换机和队列名称
        $autoQueueList = ConfigurationManager::getInstance()->getAutoCreateQueueNameList();
        $autoExchangeList = ConfigurationManager::getInstance()->getAutoCreateExchangeNameList();
        foreach ($exchanges as $exchangeName => $exchange) {
            //处理交换机
            if (!in_array($exchangeName,$autoExchangeList)) continue;
            if (!$this->autoExchange($exchangeName,$exchange)) continue;

            //处理队列
            $queues = $exchange['queues'] ?? [];
            foreach ($queues as $queueName => $queue) {
                if (!$this->autoQueue($autoQueueList,$queue,$exchangeName,$queueName)) continue;
            }
        }
    }

    /**
     * @param array $exchange
     * @param string $exchangeName
     * @return false
     */
    private function autoExchange(string $exchangeName,array $exchange) : bool
    {
        if (empty($exchangeName)) return false;
        //如果设置此值=true则会自动创建交换机
        $type = $exchange['type'] ?? 'direct';
        $passive = $exchange['passive'] ?? false;
        $durable = $exchange['durable'] ?? true;
        $autoDelete = $exchange['auto_delete'] ?? false;
        $internal = $exchange['internal'] ?? false;
        $nowait = $exchange['nowait'] ?? false;
        $arguments = $exchange['arguments'] ?? [];
        $ticket = $exchange['ticket'] ?? null;
        $this->channel->exchange_declare($exchangeName,$type,$passive,$durable,$autoDelete,$internal,$nowait,$arguments,$ticket);
        return true;
    }

    /**
     * 自动队列
     * @param array $autoQueueList 需要自动创建的队列
     * @param array $queue 队列配置
     * @param $exchangeName 交换机名称
     * @param string $queueName  队列名称
     */
    private function autoQueue(array $autoQueueList,array $queue,string $exchangeName,string $queueName) : bool
    {
        if (empty($queueName)) return false;
        $routeKey = $queue['route_key'] ?? '';
        //其它通用参数
        $nowait = $queue['nowait'] ?? false;
        $arguments = $queue['arguments'] ?? [];
        $ticket = $queue['ticket'] ?? null;
        if (in_array($queueName,$autoQueueList)) {
            $passive = $queue['passive'] ?? false;//是否检测同名队列
            $durable = $queue['durable'] ?? true;//是否开启队列持久化
            $exclusive = $queue['exclusive'] ?? false;//队列是否可以被其他队列访问
            $autoDelete = $queue['auto_delete'] ?? false;//通道关闭后是否删除队列
            //dump($queueName);
            $this->channel->queue_declare($queueName,$passive,$durable,$exclusive,$autoDelete,$nowait,$arguments,$ticket);
            //绑定
            $rs = $this->channel->queue_bind($queueName,$exchangeName,$routeKey,$nowait,$arguments,$ticket);
        }
        return true;
    }

    /**
     * 转化为标准化的AMQPMessage
     * @param mixed $messageBody
     * @param array $options
     * @return AMQPMessage
     */
    public function transformAMQPMessage($messageBody,array $options)
    {
        $message = new AMQPMessage($messageBody, $options);
        return $message;
    }

    /**
     * 获取put操作时的Options
     * @param string $messageId
     * @param string $contentType
     * @param int $mode
     * @return array
     */
    public function getPutOptions(string $messageId,string $contentType = 'text/plain',int $mode = AMQPMessage::DELIVERY_MODE_PERSISTENT) : array
    {
        $options = array(
            'content_type' => $contentType,
            'delivery_mode' => $mode,
            'message_id'    => $messageId
        );
        return $options;
    }

    /**
     * 获取messageId,如果用户未自定义设置，将自动根据消息内容生成
     * @param RabbitMqQueueArguments $rabbitMqQueueArguments
     * @param $message
     * @return string
     */
    public function getMessageId(RabbitMqQueueArguments $rabbitMqQueueArguments,$message) : string
    {
        //初始化messageId
        $messageId = (string)$rabbitMqQueueArguments->getMessageId();
        if (empty($messageId)) $messageId = md5(strval($message));
        return $messageId;
    }
    /**
     * @return mixed|string
     */
    public function getConsumerTag(): string
    {
        return $this->consumerTag;
    }

    /**
     * @return bool|mixed
     */
    public function getNoLocal(): bool
    {
        return $this->noLocal;
    }

    /**
     * @return bool|mixed
     */
    public function getNoAck(): bool
    {
        return $this->noAck;
    }

    /**
     * @return bool|mixed
     */
    public function getExclusive(): bool
    {
        return $this->exclusive;
    }

    /**
     * @return bool|mixed
     */
    public function getNoWait(): bool
    {
        return $this->noWait;
    }

}