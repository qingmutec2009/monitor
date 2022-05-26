# monitor-php

#### 介绍
MQ监听项目
本项目遵循MIT开源项目协定，使用MIT协议的开源软件作者只保留版权,而对使用者无任何其它限制。

#### 软件架构
软件架构说明


#### 安装教程

composer地址：https://packagist.org/packages/qingmutec/monitor?query=qingmutec <br/>
composer命令： swoole process manager for rabbitMQ <br/>
其它说明：
1. php版本大于等于7.2。
2. swoole版本大于v4.5.3以上

#### 使用说明

1.  配置文件自行在项目中或下方拷贝一份，放在任意位置，在调用时传入配置数组(必须)。
2.  本项目一共分成两部分，分别为多进程消费和生产者。
3.  多进程消费使用cli模式启动,可以随意整合到框架自带的Command命令或自行调用。后台任务可以使用nohup命令。
4.  生产端注意在配置文件中的queue_run_right_now参数使用就好，为true时将立即执行不会经过队列，主要用来调试。
5.  注意env文件中的true和false等配置参数转入config时的值类型转换。false和"false"是不一样的。
6.  后台运行：nohup command &   | nohup ./command >test.log&   |  nohup ./command >>test.log&
    > 说明：命令执行的结果保存到test.log中 ">"表示覆盖原文件内容（文件的日期也会自动更新），">>"表示追加内容（会另起一行，文件的日期也会自动更新）
7.  当前只支持processManager模式使用多进程，在此进程模式下消息必须是消息消费确认模式，所以"no_ack"配置必须=false。否则将不能启动项目。
8.  当前项目已经默认将"php-amqplib"添加为依赖所以可以不用单独为其设置依赖
9.  v1.3中根目录会有相应的.sh文件可以直接使用。
10. 在框架中可使用框架自带的Command命令去调用\qmmonitor\command中相应的方法完成对接。

#### 接入示例

使用框架自带的Command单独创建一个命令类，再加入以下：
```    /**
* @var \qmmonitor\command\Command
*/
private $command = null;

    /**
     * 配置信息
     * @var array
     */
    private $config = [];

    public function __construct()
    {
        $this->command = new Command();
        $this->config = config('queue');
    }

    /**
     * 测试执行函数
     * @return array
     */
    public function run()
    {
        //启动
        $this->command->start($this->config);
    }

    /**
     * 重启队列
     */
    public function restart()
    {
        $this->command->restart($this->config);
    }

    /**
     * 结束队列
     */
    public function stop()
    {
        $this->command->stop($this->config);
    }

    /**
     * 获取队列运行状态
     */
    public function list()
    {
        $list = $this->command->list();
        $outputStr = '';
        foreach ($list as $item) {
            $outputStr .= $item . "\r\n";
        }
        exit(Color::blue($outputStr));
    }
```
生产者代码如下：
```
public function put()
    {
        try {
            $queueComponent = new QueueComponent();
            $queueComponent->setExchange('direct_goods_exchange')
                ->setQueue('direct_goods_test_queue')
                ->setRouteKey('goods_test');
            //或者使用RabbitMqQueueArguments对象设置属性后传入第二个参数中
            $queueComponent->put('我是一个测试消息', null, $this->config);
        } catch (\Throwable $exception) {
            var_dump($exception->getMessage());
        }
    }
```
#### 阶段性处理
1.  先保证组件的扩展和可靠性
2.  开发能够应对阿里API接口，用于能够web查看相关信息
    

