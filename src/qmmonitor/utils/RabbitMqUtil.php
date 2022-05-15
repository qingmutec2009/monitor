<?php
namespace qmmontitor\utils;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use qmmontitor\command\Singleton;
use Swoole\Coroutine;

/**
 *
 * Class RabbitMqComponent
 * @package app\mq\component
 */
class RabbitMqUtil
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
        //检测配置是否正常
        ConfigManager::getInstance()->checkExchangeConfig();
        ConfigManager::getInstance()->checkQueueConfig();
        //初始化连接
        $this->getChannel();
        //是否自动完成交换机、队列及绑定
        $this->autoCreate();
    }

    public function getConnection()
    {
        $this->connection = new AMQPStreamConnection(
            $this->host,$this->port,$this->user,$this->password,$this->virtual,
            $this->insist,$this->loginMethod,$this->loginResponse,$this->locale,
            $this->connectionTimeout,$this->rwTimeout,$this->context,$this->keepAlive,$this->heartBeat,
            $this->channelRpcTimeout,$this->sslProtocol
        );
        return $this->connection;
    }

    public function getChannel()
    {
        $this->getConnection();
        $this->channel = $this->connection->channel();
        return $this->channel;
    }

    public function close()
    {
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * 设置qos
     * @param AMQPChannel|null $channel
     * @param $prefetchSize int
     * @param $prefetchCount
     * @param $aGlobal
     * @return AMQPChannel
     */
    public function qos(? AMQPChannel $channel = null,$prefetchSize = 1,$prefetchCount = null,$aGlobal = null) : AMQPChannel
    {
        if (empty($channel)) $channel = $this->getChannel();
        //设置prefetch_count=1。这样是告诉RabbitMQ，再同一时刻，不要发送超过1条消息给一个工作者（worker），
        //直到它已经处理了上一条消息并且作出了响应。这样，RabbitMQ就会把消息分发给下一个空闲的工作者（worker），轮询、负载均衡配置
        $channel->basic_qos(null, $prefetchSize, null);
        $this->channel = $channel;
        return $channel;
    }

    /**
     * rabbitmq消费
     * @param string $queueName
     * @param $callBack
     * @param AMQPChannel $channel
     */
    public function consumer(string $queueName,\Closure $callBack,? AMQPChannel $channel)
    {
        if (empty($channel)) $channel = $this->channel;
        $consumerTag = $channel->basic_consume(
            $queueName,
            RabbitMqComponent::getInstance()->getConsumerTag(),
            RabbitMqComponent::getInstance()->getNoLocal(),
            RabbitMqComponent::getInstance()->getNoAck(),
            RabbitMqComponent::getInstance()->getExclusive(),
            RabbitMqComponent::getInstance()->getNoWait(),
            $callBack
        );
        #监听消息，一有消息，立马就处理,多进程中不使用，
        while(count($channel->callbacks)) {
            $channel->wait();
        }
    }

    /**
     * 回调
     * @param string $nowQueueConfig 当前队列配置
     * @param string $queueName 队列名称
     * @param bool $enableCoroutine 是否开启协程 临时参数
     * @param $workerId 当前工作id 0、1、2、3、4这样
     * @param int $pid 当前父级工作进程id
     * @return \Closure
     */
    public function consumerCallBack(array $nowQueueConfig,string $queueName,bool $enableCoroutine,int $workerId,$pid = 0) : \Closure
    {
        return  function (AMQPMessage $msg) use ($nowQueueConfig,$queueName,$enableCoroutine,$workerId,$pid){
            echo " [workerId={$workerId},pid={$pid}] Received ", $msg->body, "\n";
            //$msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            //todo $enableCoroutine变量测试时使用
            if ($enableCoroutine) {
                Coroutine::sleep(3);
            } else {
                sleep(3);
            }
            echo "{$workerId}模拟处理完成，即将进入真实的Job执行文件中".PHP_EOL;
            //处理当前整合信息
            $jobArguments = new JobArguments();
            $jobArguments->setMessage($msg->body);
            $jobArguments->setWorkerId($workerId);
            $jobArguments->setPid($workerId);
            $jobArguments->setQueueName($queueName);
            $jobArguments->setNowQueueConfig($nowQueueConfig);
            //处理业务逻辑
            Core::getInstance()->runJob($jobArguments);
            //消息确认
            if (!$this->getNoAck()) {
                $msg->ack();
            }
        };
    }

    /**
     * 写入队列 todo 本方法可以通过interface或abstract来约束名称
     * @param array $config
     * @param string $message
     * @param RabbitMqQueueArguments $rabbitMqQueueArguments
     * @throws \think\Exception
     */
    public function put(array $config,string $message,RabbitMqQueueArguments $rabbitMqQueueArguments)
    {
        //获取队列名称及Job绑定信息
        $queuesConfig = ConfigManager::getInstance()->getConfig('queue');
        $runRightNow = (bool)ConfigManager::getInstance()->getConfig('queue_run_right_now');
        if ($runRightNow) {
            $queueName = $rabbitMqQueueArguments->getQueueName();
            //以下为兼容处理
            if (empty($queueName)) {
                //根据配置拿到相应的job信息
                $queueName = ConfigManager::getInstance()
                    ->getQueueByExchangeName($rabbitMqQueueArguments->getExchange(),$rabbitMqQueueArguments->getRouteKey());
                $rabbitMqQueueArguments->setQueueName($queueName);
            } else {
                //检测参数是否正常
                ConfigManager::getInstance()->checkQueueExist($rabbitMqQueueArguments);
            }
            $nowQueueConfig = $queuesConfig[$queueName] ?? [];
            if (empty($nowQueueConfig)) throw new \Exception("当前配置queue中缺少{$queueName}的相应配置");
            //设置参数
            $jobArguments = new JobArguments();
            $jobArguments->setQueueName($queueName)->setNowQueueConfig($nowQueueConfig)->setMessage($message);
            if (PHP_SAPI == 'cli') {
                $jobArguments->setPid(posix_getpid());
            }
            //执行任务
            Core::getInstance()->runJob($jobArguments);
            return $runRightNow;
        }
        //投入到rabbitMq
        $channel = RabbitMqComponent::getInstance($config)->getChannel();
        $messageBody = RabbitMqComponent::getInstance($config)->transformAMQPMessage($message);
        $channel->basic_publish($messageBody,$rabbitMqQueueArguments->getExchange(),$rabbitMqQueueArguments->getRouteKey());
        return $runRightNow;
    }

    /**
     * 根据配置文件初始化交换机及队列及绑定
     */
    private function autoCreate()
    {
        $exchanges = ConfigManager::getInstance()->getConfig('exchanges');
        //获取自动创建的队列的交换机和队列名称
        $autoQueueList = ConfigManager::getInstance()->getAutoCreateQueueNameList();
        $autoExchangeList = ConfigManager::getInstance()->getAutoCreateExchangeNameList();
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
        $durable = $exchange['durable'] ?? false;
        $autoDelete = $exchange['auto_delete'] ?? true;
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
            $durable = $queue['durable'] ?? false;//是否开启队列持久化
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
     * @param string $messageBody
     * @return AMQPMessage
     */
    public function transformAMQPMessage(string $messageBody)
    {
        $message = new AMQPMessage($messageBody, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        return $message;
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