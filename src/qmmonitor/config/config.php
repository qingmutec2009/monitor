<?php

use PhpAmqpLib\Message\AMQPMessage;

return [
    //通用设置
    //'temp_dir'  => '',//默认是temp目录
    //'pid_file'  => '',//默认是pid文件在temp目录中
    'retry'     => 4,//默认=1
    'queue_run_right_now'   => false,
    'exception_closure' => function(\Throwable $throwable, AMQPMessage $message) {
        //有一个或多个任务时执行时捕获异常或者错误，一旦在异常或错误引起重试次数超标后则会回调此方法
        echo '我是被错误或异常触发的回调' . PHP_EOL;
        //var_dump($throwable->getMessage());
        //var_dump($throwable->getFile());
        //var_dump($throwable->getLine());
        $message->ack();
        return true;
    },
    //exchange
    'exchanges'  => [
        'direct_qm_goods_exchange'  => [
            'queues'        => [
                'direct_qm_goods_input_queue'   => [
                    'route_key'       => 'goods_input',
                    'passive'         => false,//如果设置true存在则返回OK，否则就报错。设置false存在返回OK，不存在则自动创建
                    'durable'         => false,//是否持久化，设置false是存放到内存中的，RabbitMQ重启后会丢失
                    'exclusive'       => false,
                    'auto_delete'     => false,//是否自动删除，当最后一个消费者断开连接之后队列是否自动被删除
                ],
                'direct_qm_goods_output_queue'  => [
                    'route_key'       => 'goods_output',
                    'passive'         => false,//如果设置true存在则返回OK，否则就报错。设置false存在返回OK，不存在则自动创建
                    'durable'         => false,//是否持久化，设置false是存放到内存中的，RabbitMQ重启后会丢失
                    'exclusive'       => false,
                    'auto_delete'     => false,//是否自动删除，当最后一个消费者断开连接之后队列是否自动被删除
                ],
            ],
            'type'          => 'direct',//交换机类型，分别为direct/fanout/topic
            'passive'       => false,//如果设置true存在则返回OK，否则就报错。设置false存在返回OK，不存在则自动创建
            'durable'       => false,//是否持久化，设置false是存放到内存中的，RabbitMQ重启后会丢失
            'auto_delete'   => false,//是否自动删除，当最后一个消费者断开连接之后队列是否自动被删除
        ],
        'topic_qm_order_exchange'   => [
            'queues'        => [
                'topic_qm_order_input_queue'    => [
                    'route_key'        => 'order_input',
                ],
            ],
            'type'          => 'topic',//交换机类型，分别为direct/fanout/topic
            'passive'       => false,//是否检测同名队列
            'durable'       => false,//交换机是否开启持久化
            'auto_delete'   => false,//通道关闭后是否删除队列
        ],
        'fanout_qm_image_exchange'  => [
            'queues'        => [
                'fanout_qm_image_upload_queue'  => [
                    //fanout模式下不使用route_key
                ],
            ],
            'type'          => 'fanout',//交换机类型，分别为direct/fanout/topic
            'passive'       => false,//是否检测同名队列
            'durable'       => false,//交换机是否开启持久化
            'auto_delete'   => false,//通道关闭后是否删除队列
        ],
    ],

    //任务相关
    'queue' => [
        //公共模块队列
        'fanout_qm_image_upload_queue' => [
            'job' => [
                //可以多个任务
                \qmmonitor\test\TestJob::class,
            ],
            'count' => 2,
            'auto_create'   => true,//为true时将自动做创建绑定关系，为false时将由使用者自行维护
            'extend_params' => [],//附带的自定义参数
            'auto_ack'      => true,//为true时将在执行完所有任务后自动确认，为false时将需要人为控制确认,默认=true
        ],
        'direct_qm_goods_input_queue' => [
            'job' => [
                //可以多个任务
                \qmmonitor\test\TestJob::class,
            ],
            'count' => 2,
            'auto_create'   => true,//为true时将自动做创建绑定关系，为false时将由使用者自行维护
            'extend_params' => [],//附带的自定义参数
            'auto_ack'      => false,//为true时将在执行完所有任务后自动确认，为false时将需要人为控制确认,默认=true
        ],
    ],

    'amqp'      => [
        //服务本身连接配置
        'host'          => '127.0.0.1',
        'port'          => 5672,
        'user'          => 'root',
        'password'      => '584520Wang',
        'virtual'       => '/',
        //生产API参数配置
        'keep_alive'    => true,//连接保持
        'connection_timeout'    => 60,//连接超时时间
        'heart_beat'    => 15,//心跳检测
        //消费API参数配置
        'consumer_tag'  => '',//消费者标识符
        'no_local'      => false,//不接受此使用者发布的消息
        'no_ack'        => false,//使用者使用自动确认模式,processManager模式下必须为false
        'exclusive'     => false,//请求独占使用者访问
        'nowait'        => false,//不等待
        'ticket'        => null,
        'arguments'     => [],
    ],
];