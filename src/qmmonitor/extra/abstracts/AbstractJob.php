<?php


namespace qmmonitor\extra\abstracts;

/**
 * 工作基类
 * Class AbstractJob
 * @package qmmonitor\extra\abstracts
 */
abstract class AbstractJob
{
    /**
     * 注册参数
     * 注册、兼容旧版本
     * @param $param
     * @return mixed
     */
    abstract public function register($param);

    /**
     * 初始化
     * @return mixed
     */
    abstract public function initialize();

    /**
     * 任务入口
     * 如果没有自定义操作,不要对异常进行捕获
     * 队列本身的异常扑捉更加完善
     * try后的异常将不会在队列的失败列表中
     *
     * @return mixed
     */
    abstract public function handle();
}