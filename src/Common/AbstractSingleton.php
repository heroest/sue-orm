<?php

namespace Sue\LegacyModel\Common;

abstract class AbstractSingleton
{
    private static $instance = [];

    protected function __construct()
    {
    }

    /**
     * 获取实例
     *
     * @return static
     */
    public static function getInstance()
    {
        $class = get_called_class();
        return isset(self::$instance[$class])
            ? self::$instance[$class] 
            : self::$instance[$class] = new $class();
    }
}
