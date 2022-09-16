<?php
namespace qmmonitor\core;


use qmmonitor\extra\Color;
use qmmonitor\extra\pojo\RabbitMqQueueArguments;
use qmmonitor\extra\traits\Singleton;
use qmmonitor\helper\PhpHelper;

class ConfigurationManager
{
    use Singleton;

    /**
     * 交换机
     * @var array
     */
    public $config = [];

    public function __construct()
    {

    }

    /**
     * 加载配置
     * @param array $config
     * @return array
     * @throws \Exception
     */
    public function loadConfig(array $config = []) : array
    {
        //指定配置文件
        if (empty($config)) {
            $configFile = ConfigurationManager::getInstance()->getDefaultConfigFile();
            if (empty($configFile) || !is_file($configFile)) exit(Color::error("当前默认配置文件不存在").PHP_EOL);
            $config = include $configFile;
            if (empty($config)) exit(Color::error("当前默认配置文件内容为空").PHP_EOL);
        }
        $this->config = $config;
        $this->commonConfig();
        $this->checkExchangeConfig();
        $this->checkQueueConfig();
        return $config;
    }

    /**
     * 获取默认的配置文件目录
     * @return string
     */
    private function getDefaultConfigPath() : string
    {
        return MONITOR_ROOT .DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'qmmonitor' . DIRECTORY_SEPARATOR . 'config';
    }

    /**
     * 获取默认的配置文件带路径
     * @param string $fileName
     * @return string
     */
    public function getDefaultConfigFile(string $fileName = 'config.php')
    {
        return $this->getDefaultConfigPath() . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * 获取配置
     * @param string $name
     * @param null $default 默认值
     * @return array|mixed
     */
    public function getConfig(string $name,$default = null)
    {
        $name = strtolower($name);
        if (strpos($name,'.') !== false) {
            $name    = explode('.', $name);
            $name[0] = strtolower($name[0]);
            $config  = $this->config;

            // 按.拆分成多维数组进行判断
            foreach ($name as $val) {
                if (isset($config[$val])) {
                    $config = $config[$val];
                } else {
                    return $default;
                }
            }
            return $config;
        }
        return empty($name) ? $this->config : $this->config[$name] ?? $default;
    }

    /**
     * 设置
     * @param string $key
     * @param $value
     */
    public function setConfig(string $key,$value)
    {
        $this->config[$key] = $value;
    }

    /**
     * 检测参数
     * @throws \Exception
     */
    public function checkExchangeConfig()
    {
        $exChangesConfig = $this->getConfig('exchanges');
        foreach ($exChangesConfig as $exchangeName => $exChange) {
            if (empty($exchangeName)) exit(Color::error('交换机名称不能为空'));
            $queues = $exChange['queues'] ?? [];
            if (empty($queues)) exit(Color::error("当前交换机{$exchangeName}必须配置队列信息"));
            $exChange['type'] = $exChange['type'] ?? 'direct';
            foreach ($queues as $queueName => $queue) {
                if (empty($queueName)) exit(Color::error('队列名称不能为空'));
                if ($exChange['type'] == 'direct' || $exChange['type'] == 'topic') {
                    if (empty($queue['route_key'])) {
                        exit(Color::error("当前队列名称为{$queue['name']}在模式{$exChange['type']}下需要指定route_key"));
                    }
                }
            }
        }
    }

    /**
     * 检查队列配置
     * @throws \Exception
     */
    public function checkQueueConfig()
    {
        $queueConfig = $this->getConfig('queue');
        $queueNames = $this->getQueueNames();
        foreach ($queueConfig as $queueName => $queueInfo) {
            if (empty($queueInfo['job'])) exit(Color::error('队列执行任务配置不能为空'));
            //查看队列名称是否注册在exchange中
            if (!in_array($queueName,$queueNames)) exit(Color::error("当前队列名称{$queueName}不在exchange中"));
            if (empty($queueInfo['count'])) $queueConfig[$queueName]['count'] = 1;
            if (is_string($queueInfo['job'])) $queueConfig[$queueName]['job'] = explode(',',$queueInfo['job']);
        }
        $this->setConfig('queue',$queueConfig);
    }

    /**
     * 获取配置中的所有的队列名称
     * @return array
     */
    public function getQueueNames() : array
    {
        $exchangesonfig = $this->getConfig('exchanges');
        $queueNames = [];
        foreach ($exchangesonfig as $exchange) {
            $queues = $exchange['queues'];
            $temps = array_keys($queues);
            $queueNames = array_merge_recursive($queueNames,$temps);
        }
        return $queueNames;
    }

    /**
     * 获取配置中所有的交换机名称
     * @return array
     */
    public function getExchangeNames() : array
    {
        $exchangesonfig = $this->getConfig('exchanges');
        return array_keys($exchangesonfig);
    }

    /**
     * 根据队列名称获取交换机名称
     * @param string $queueName
     * @return string
     */
    public function getExchangeNameByQueueName(string $queueName) : string
    {
        $exchangesonfig = $this->getConfig('exchanges');
        foreach ($exchangesonfig as $exchangeName => $exchange) {
            $queues = $exchange['queues'];
            foreach ($queues as $nowQueueName => $queue) {
                if ($queueName == $nowQueueName) {
                    return $exchangeName;
                }
            }
        }
        return "";
    }

    /**
     * 获取自动创建相关的队列名
     * @return array
     */
    public function getAutoCreateQueueNameList()
    {
        $queuesConfig = $this->getConfig('queue');
        $queueList = [];
        foreach ($queuesConfig as $queueName => $queueConfig) {
            if (!empty($queueConfig['auto_create']) && $queueConfig['auto_create'] === true) {
                array_push($queueList,$queueName);
            }
        }
        return $queueList;
    }

    /**
     * 获取自动创建的交换机名称,根据queue中配置的自动创建属性
     * @return array
     */
    public function getAutoCreateExchangeNameList() : array
    {
        //获取自动创建的队列集合
        $autoQueueNameList = $this->getAutoCreateQueueNameList();
        //找出exchange
        $exchanges = $this->getConfig('exchanges');
        $autoExchangeNameList = [];
        foreach ($autoQueueNameList as $autoQueueName) {
            foreach ($exchanges as $exchangeName => $exchange) {
                if (empty($exchangeName)) continue;
                $queues = $exchange['queues'] ?? [];
                if (isset($queues[$autoQueueName])) {
                    array_push($autoExchangeNameList,$exchangeName);
                }
            }
        }
        return $autoExchangeNameList;
    }

    /**
     * 根据exchange和route_key查找其队列名称
     * @param string $exchangeName
     * @param string $routeKey
     * @return string
     * @throws \Exception
     */
    public function getQueueByExchangeName(string $exchangeName,string $routeKey) : string
    {
        if (empty($exchangeName)) exit(Color::error('exchange不能为空'));
        $exchangesConfig = $this->getConfig('exchanges');
        $config = $exchangesConfig[$exchangeName] ?? [];
        if (empty($config)) exit(Color::error("当前exchange={$exchangeName}未配置"));
        $queues = $config['queues'] ?? [];
        if (empty($queues)) exit(Color::error("当前交换机{$exchangeName}必须配置队列信息"));
        foreach ($queues as $queueName => $queueInfo) {
            if ($queueInfo['route_key'] == $routeKey) {
                return $queueName;
            }
        }
        exit(Color::error("当前exchange={$exchangeName}和route_key={$routeKey}未能匹配到对应的除名名称，请检查配置"));
    }

    /**
     * 根据交换机名称和队列名称获取路由key
     * @param string $exchangeName
     * @param string $queueName
     * @return mixed|string
     */
    public function getRouteKeyByExchangeNameAndQueueName(string $exchangeName,string $queueName)
    {
        $exchangesConfig = $this->getConfig('exchanges');
        $exchangeConfig = $exchangesConfig[$exchangeName] ?? [];
        $queues = $exchangeConfig['queues'] ?? [];
        $queueConfig = $queues[$queueName] ?? [];
        return $queueConfig['route_key'] ?? '';
    }

    /**
     * 检查队列是否存在
     * @param RabbitMqQueueArguments $rabbitMqQueueArguments
     * @throws \Exception
     */
    public function checkQueueExist(RabbitMqQueueArguments $rabbitMqQueueArguments)
    {
        $exchanges = $this->getConfig('exchanges');
        $exchangeName = $this->getExchangeNameByQueueName($rabbitMqQueueArguments->getQueueName());
        if (empty($exchangeName)) exit(Color::error("配置中无法匹配到当前队列名={$exchangeName}"));
        $queues = $exchanges[$exchangeName]['queues'];
        $queueName = $rabbitMqQueueArguments->getQueueName();
        if (empty($queueName)) exit(Color::error('RabbitMqQueueArguments对象中必须设置队列名称'));
        $queue = $queues[$queueName];
        if (!empty($queue)) {
            if ($queue['route_key'] != $rabbitMqQueueArguments->getRouteKey()) {
                exit(Color::error('路由key配置异常'));
            }
        }
    }

    /**
     * 通用配置初始化
     */
    public function commonConfig()
    {
        $retry = (int)$this->config['retry'] ?? 1;
        $this->config['retry'] = $retry;
        $tempDir = $this->config['temp_dir'] ?? 'temp';
        $this->config['temp_dir'] = $tempDir;
        $pidFile = $this->config['pid_file'] ?? 'pid.pid';
        $this->config['pid_file'] = $tempDir.DIRECTORY_SEPARATOR.$pidFile;
        $reloadMaxTime = $this->config['reload_max_wait_time'] ?? 15;
        $this->config['reload_max_wait_time'] = $reloadMaxTime;
        $maxMemoryLimit = $this->config['max_memory_limit'] ?? 100;
        if (strpos($maxMemoryLimit,'M') === false) {
            $maxMemoryLimit = $maxMemoryLimit.'M';
        }
        $this->config['max_memory_limit'] = $maxMemoryLimit;
        $debug = $this->config['debug'] ?? false;
        if (!is_bool($debug)) {
            $debug = $debug == 'true' ? true : false;
        }
        $this->config['debug'] = $debug;
        $reConnectionInterval = (int)$this->config['reconnection_interval'] ?? 0;
        $this->config['reconnection_interval'] = $reConnectionInterval;
    }
}