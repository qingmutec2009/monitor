<?php
use qmmonitor\extra\pojo\RabbitMqQueueArguments;

include "src/qmmonitor/bootstrap.php";
//    /bin/sh ./start.sh
$data = [
    ['id' => 1,'name' => 'a',],
    ['id' => 2,'name' => 'b',],
    ['id' => 3,'name' => 'c',],
];
//test();die('over');
$command = new \qmmonitor\command\Command(\qmmonitor\command\Command::APPLICATION_NAME);
//处理message
$message = json_encode($data,JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
//$message = file_get_contents('1.txt');
//direct模式发布
//$setting = ['exchange'=>'direct_qm_goods_exchange','route_key'=>'goods_input',''];
/*$rabbitMqQueueArguments = new RabbitMqQueueArguments();
$rabbitMqQueueArguments->setExchange('direct_qm_goods_exchange')
    ->setRouteKey('goods_input')
    ->setQueueName('direct_qm_goods_input_queue');
$command->put($message,$rabbitMqQueueArguments);
die(1);*/

//以下测试模拟多进程消费
$command->start();
