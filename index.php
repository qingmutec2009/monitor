<?php
//include 'vendor/autoload.php';
use qmmonitor\extra\pojo\RabbitMqQueueArguments;

include "src/qmmonitor/bootstrap.php";

/*$config = [
    'host'          => '127.0.0.1',
    'port'          => 5672,
    'user'          => 'root',
    'password'      => '584520Wang',
    'virtual'       => '/',
    'keep_alive'    => true,
    'connection_timeout'    => 60,
    'heart_beat'    => 15,

];*/
$data = [
    ['id' => 1,'name' => 'a',],
    ['id' => 2,'name' => 'b',],
    ['id' => 3,'name' => 'c',],
];
$command = new \qmmonitor\command\Command();
//处理message
$message = json_encode($data,JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
//direct模式发布
//$setting = ['exchange'=>'direct_qm_goods_exchange','route_key'=>'goods_input',''];
$rabbitMqQueueArguments = new RabbitMqQueueArguments();
$rabbitMqQueueArguments->setExchange('direct_qm_goods_exchange')
    ->setRouteKey('goods_input')
    ->setQueueName('direct_qm_goods_input_queue');
$command->put($message,$rabbitMqQueueArguments);
die(1);
//以下测试模拟生产者
\qmmonitor\core\RabbitMqManager::getInstance()->put($config,1);

//以下测试模拟多进程消费
$command->run();

