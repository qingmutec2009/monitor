<?php
return [
    //临时目录
    'temp_dir'    => 'temp',
    'log_dir'     => 'log',
    //'pid_file'      => 'pid',
    'queue_run_right_now'   => false,
    //'daemon'        => false,
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
                \app\mq\job\TestJob::class,
            ],
            //'INTERVAL' => 5,
            'count' => 2,
            'auto_create'   => true,
        ],
        'direct_qm_goods_input_queue' => [
            'job' => [
                \app\mq\job\TestJob::class,
            ],
            //'INTERVAL' => 5,
            'count' => 2,
            'auto_create'   => true,
        ],
    ],

    'amqp'      => [
        'host'          => env('RABBITMQ.HOST','127.0.0.1'),
        'port'          => (int)env('RABBITMQ.PORT',5672),
        'user'          => env('RABBITMQ.USER','root'),
        'password'      => env('RABBITMQ.PASSWORD',''),
        'virtual'       => env('RABBITMQ.VIRTUAL','/'),
        //生产
        'keep_alive'    => (bool)env('RABBITMQ.KEEP_ALIVE',true),//连接保持
        'connection_timeout'    => (int)env('RABBITMQ.CONNECTION_TIMEOUT',60),//连接超时时间
        'heart_beat'    => (int)env('RABBITMQ.HEART_BEAT',15),//心跳检测
        //消费
        'consumer_tag'  => '',//消费者标识符
        'no_local'      => false,//不接受此使用者发布的消息
        'no_ack'        => false,//使用者使用自动确认模式
        'exclusive'     => false,//请求独占使用者访问
        'nowait'        => false,//不等待
        'ticket'        => null,
        'arguments'     => [],
    ],
];