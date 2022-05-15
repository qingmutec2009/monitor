<?php

use qmmonitor\extra\pojo\RabbitMqQueueArguments;

class Put
{
    /**
     *  fanout： 该值会被忽略，因为该类型的交换机会把所有它知道的队列发消息，无差别区别
    direct  只有精确匹配该路由键的队列，才会发送消息到该队列
    topic   只有正则匹配到的路由键的队列，才会发送到该队列
     * @param array $config
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function push(array $config)
    {
        //写入数据,模拟生产者
        $data = Db::table('xfhz_record')->limit(0,10)->order('record_id asc')->select()->toArray();
        foreach ($data as $datum) {
            //处理message
            $message = json_encode($datum,JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
            //direct模式发布
            //$setting = ['exchange'=>'direct_qm_goods_exchange','route_key'=>'goods_input',''];
            $rabbitMqQueueArguments = new RabbitMqQueueArguments();
            $rabbitMqQueueArguments->setExchange('direct_qm_goods_exchange')
                ->setRouteKey('goods_input')
                ->setQueueName('direct_qm_goods_input_queue');
            \qmmonitor\core\RabbitMqManager::getInstance($config)->put($config,$message,$rabbitMqQueueArguments);
            //$channel->basic_publish($message,'direct_qm_goods_exchange','goods_input');
            //topic模式发布
            /*RabbitMqComponent::getInstance()->getChannel()
                ->basic_publish($message,'topic_qm_order_exchange','order_input');*/
            //fanout模式发布
            //$channel->basic_publish("fanout_qm_image_exchange".$message,'fanout_qm_image_exchange');
        }

        //以下测试
        $data = Db::table('xfhz_record')->limit(0,5)->order('record_id desc')->select()->toArray();
        foreach ($data as $datum) {
            //处理message
            $message = json_encode($datum,JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
            //direct模式发布
            //$channel->basic_publish($message,'direct_qm_goods_exchange','goods_input');
            //topic模式发布
            /*RabbitMqComponent::getInstance()->getChannel()
                ->basic_publish($message,'topic_qm_order_exchange','order_input');*/
            $rabbitMqQueueArguments = new RabbitMqQueueArguments();
            $rabbitMqQueueArguments->setExchange('fanout_qm_image_exchange')
                ->setQueueName('fanout_qm_image_upload_queue');
            //fanout模式发布
            //RabbitMqComponent::getInstance($config)->put($config,$message,$rabbitMqQueueArguments);
        }
        dump('队列推送完成');
    }
}