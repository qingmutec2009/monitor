<?php
namespace qmmontitor\utils;


class ConfigurationUtil
{
    use Singleton;

    /**
     * 交换机
     * @var array
     */
    public $config = [];

    public function __construct()
    {
        defined('SWOOLE_VERSION') or define('SWOOLE_VERSION', intval(phpversion('swoole')));
        defined('MONITOR_ROOT') or define('MONITOR_ROOT', realpath(getcwd()));
        $configContent = $this->loadConfig();
        $this->config = $configContent;
    }

    /**
     * 加载配置
     * @return array
     * @throws \Exception
     */
    private function loadConfig() : array
    {
        $configPath = app()->getAppPath().DIRECTORY_SEPARATOR.'mq'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php';
        if (!is_file($configPath)) throw new \Exception('配置文件不存在');
        $content = include ($configPath);
        return $content;
    }

    /**
     * 获取配置
     * @param string $name
     * @return array|mixed
     */
    public function getConfig(string $name)
    {
        return empty($name) ? $this->config : $this->config[$name] ?? '';
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
        $exChangesConfig = ConfigManager::getInstance()->getConfig('exchanges');
        foreach ($exChangesConfig as $exchangeName => $exChange) {
            if (empty($exchangeName)) throw new \Exception('交换机名称不能为空');
            $queues = $exChange['queues'] ?? [];
            if (empty($queues)) throw new \Exception("当前交换机{$exchangeName}必须配置队列信息");
            foreach ($queues as $queueName => $queue) {
                if (empty($queueName)) throw new \Exception('队列名称不能为空');
                if ($exChange['type'] == 'direct' || $exChange['type'] == 'topic') {
                    if (empty($queue['route_key'])) {
                        throw new \Exception("当前队列名称为{$queue['name']}在模式{$exChange['type']}下需要指定route_key");
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
            if (empty($queueInfo['job'])) throw new \Exception('队列执行任务配置不能为空');
            //查看队列名称是否注册在exchange中
            if (!in_array($queueName,$queueNames)) throw new \Exception("当前队列名称{$queueName}不在exchange中");
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
        $queuesConfig = ConfigManager::getInstance()->getConfig('queue');
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
        $exchanges = ConfigManager::getInstance()->getConfig('exchanges');
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
        if (empty($exchangeName)) throw new \Exception('exchange不能为空');
        $exchangesonfig = $this->getConfig('exchanges');
        $config = $exchangesonfig[$exchangeName] ?? [];
        if (empty($config)) throw new \Exception("当前exchange={$exchangeName}未配置");
        $queues = $config['queues'] ?? [];
        if (empty($queues)) throw new \Exception("当前交换机{$exchangeName}必须配置队列信息");
        foreach ($queues as $queueName => $queueInfo) {
            if ($queueInfo['route_key'] == $routeKey) {
                return $queueName;
            }
        }
        throw new \Exception("当前exchange={$exchangeName}和route_key={$routeKey}未能匹配到对应的除名名称，请检查配置");
    }

    /**
     * 检查队列是否存在
     * @param RabbitMqQueueArguments $rabbitMqQueueArguments
     * @throws \Exception
     */
    public function checkQueueExist(RabbitMqQueueArguments $rabbitMqQueueArguments)
    {
        $exchanges = ConfigManager::getInstance()->getConfig('exchanges');
        $exchangeName = $this->getExchangeNameByQueueName($rabbitMqQueueArguments->getQueueName());
        if (empty($exchangeName)) throw new \Exception("配置中无法匹配到当前队列名={$exchangeName}");
        $queues = $exchanges[$exchangeName]['queues'];
        $queueName = $rabbitMqQueueArguments->getQueueName();
        if (empty($queueName)) throw new \Exception('RabbitMqQueueArguments对象中必须设置队列名称');
        $queue = $queues[$queueName];
        if (!empty($queue)) {
            if ($queue['route_key'] != $rabbitMqQueueArguments->getRouteKey()) {
                throw new \Exception('路由key配置异常');
            }
        }
    }
}