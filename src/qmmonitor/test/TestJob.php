<?php
namespace qmmonitor\test;

use qmmonitor\command\Command;
use qmmonitor\extra\abstracts\AbstractJob;
use qmmonitor\extra\pojo\JobArguments;
use qmmonitor\extra\pojo\RabbitMqQueueArguments;
use think\facade\Db;

/**
 * 测试工作任务
 * Class TestJob
 * @package qmmonitor\test
 */
class TestJob extends AbstractJob
{


    public function register($param)
    {
        $res = @json_decode($this->params,true);
        $this->params = empty($res) ? $this->params : $res;
    }


    public function handle()
    {
        $queueName = $this->jobArguments->getQueueName();
        echo "当前队列名称{$queueName}输出:".$this->params.PHP_EOL;
        //throw new \Exception("我是一个测试异常");
        //file_put_contents('consumer.txt',$queueName.'+'.date('Y-m-d H:i:s'));
        //测试转入其它队列
        //$command = new Command();
        /*if ($queueName == 'direct_qm_goods_input_queue') {
            $rabbitMqQueueArguments = new RabbitMqQueueArguments();
            $rabbitMqQueueArguments->setQueueName('fanout_qm_image_upload_queue')
                ->setExchange('fanout_qm_image_exchange');
            $command->put($this->params,$rabbitMqQueueArguments);
        }*/
        $message_id = $this->jobArguments->getAMQPmessage()->get_properties()['message_id'];
        $rand = [10000,1000000,100000,10,500000,900000];
        $data = [];$max = $rand[array_rand($rand)];
        for ($i = 0;$i < $max;$i ++) {
            $data[] = $i;
        }
        //sleep(2);
        //手动确认
        $this->ack();
        //var_dump($this->params);
        //var_dump($this->jobArguments->getConfigurationManager()->getConfig('queue'));
        //var_dump($this->jobArguments->getChannel());
        //var_dump("更新完成");
    }
}