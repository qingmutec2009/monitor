<?php
//include 'vendor/autoload.php';
use qmmonitor\extra\pojo\RabbitMqQueueArguments;

include "src/qmmonitor/bootstrap.php";
stop();

function stop()
{
    $command = new \qmmonitor\command\Command();
    $command->reload();
}
