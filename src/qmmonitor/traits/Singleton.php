<?php

namespace qmmontitor\command;

/**
 * 单例模式trait
 * Class Singleton
 * @package app\object
 */
trait Singleton
{
    private static $instance;

    /**
     * @param mixed ...$args
     * @return static
     */
    public static function getInstance(...$args)
    {
        if(!isset(static::$instance)){
            static::$instance = new static(...$args);
        }
        return static::$instance;
    }

}