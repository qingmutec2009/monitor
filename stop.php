<?php
//include 'vendor/autoload.php';
use qmmonitor\extra\pojo\RabbitMqQueueArguments;

include "src/qmmonitor/bootstrap.php";
stop();

function stop()
{
    $command = new \qmmonitor\command\Command(\qmmonitor\command\Command::APPLICATION_NAME);
    $command->stop();
}
