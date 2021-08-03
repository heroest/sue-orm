<?php

namespace Sue\LegacyModel\Model\Laravel;

use Sue\LegacyModel\Common\Config;
use Sue\LegacyModel\Driver\ConnectionPool;
use Sue\LegacyModel\Driver\Contracts\ConnectionInterface;

class DB
{
    private function __construct()
    {
    }

    public static function connection($connection_name)
    {
        $pool = ConnectionPool::build();
        return $pool->connection($connection_name);
    }

    public static function addConnection($connection_name, $mixed)
    {
        $pool = ConnectionPool::build();
        return $pool->addConnection($connection_name, $mixed);
    }

    public static function beginTransaction()
    {
        $connection = self::defaultConnection();
        return $connection->beginTransaction();
    }

    public static function inTransaction()
    {
        $connection = self::defaultConnection();
        return $connection->inTransaction();
    }

    public static function commit()
    {
        $connection = self::defaultConnection();
        return $connection->commit();
    }

    public static function rollback()
    {
        $connection = self::defaultConnection();
        return $connection->rollback();
    }

    public static function getQueryLog()
    {
        $connection = self::defaultConnection();
        return $connection->getQueryLog();
    }

    /**
     * 获取默认链接
     *
     * @return ConnectionInterface
     */
    private static function defaultConnection()
    {
        $pool = ConnectionPool::build();
        return $pool->connection(Config::get('default_connection', ''));
    }
}
