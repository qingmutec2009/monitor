<?php
//include 'vendor/autoload.php';
use qmmonitor\extra\pojo\RabbitMqQueueArguments;
include "src/qmmonitor/bootstrap.php";
put();
//    /bin/sh ./put.sh
function put()
{
    $command = new \qmmonitor\command\Command('monitor');
    $rabbitMqQueueArguments = new RabbitMqQueueArguments();
    $rabbitMqQueueArguments->setQueueName('direct_qm_goods_input_queue')
        ->setExchange('direct_qm_goods_exchange')
        ->setProducerConfirm(true)
        ->setRouteKey('goods_input');
    $i = 0;
    while (true) {
        $rabbitMqQueueArguments->setMessageId(uniqid());
        $command->put('测试消息:' . $i.':'.date('Y-m-d H:i:s'),$rabbitMqQueueArguments);
        sleep(3);
        $i ++;
    }

}
