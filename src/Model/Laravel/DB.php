<?php

namespace Sue\LegacyModel\Model\Laravel;

use Sue\LegacyModel\Model\Component\Expression;
use Sue\LegacyModel\Model\Laravel\Query;
use Sue\LegacyModel\Common\Config;
use Sue\LegacyModel\Driver\ConnectionPool;
use Sue\LegacyModel\Driver\Contracts\ConnectionInterface;

/**
 * 数据库操作类
 */
class DB
{
    private function __construct()
    {
    }

    /**
     * 设置数据库连接或返回Query对象
     *
     * @param string $connection_name
     * @return Query
     */
    public static function connection($connection_name)
    {
        return new Query($connection_name);
    }

    /**
     * 添加一条数据库连接的配置
     *
     * @param string $connection_name
     * @param array|\mysqli|\PDO|resource $mixed
     * @return void
     */
    public static function addConnection($connection_name, $mixed)
    {
        (ConnectionPool::build())->addConnection($connection_name, $mixed);
    }

    /**
     * 开始数据库事务
     *
     * @param string $connection_name 连接名称，如果没有则使用默认数据库连接
     * @return boolean
     */
    public static function beginTransaction($connection_name = '')
    {
        $connection = $connection_name 
            ? (ConnectionPool::build())->connection($connection_name)
            : self::defaultConnection();
        return $connection->beginTransaction();
    }

    /**
     * 检查是否已经处于事务中
     *
     * @param string $connection_name 连接名称，如果没有则使用默认数据库连接
     * @return boolean
     */
    public static function inTransaction($connection_name = '')
    {
        $connection = $connection_name 
            ? (ConnectionPool::build())->connection($connection_name)
            : self::defaultConnection();
        return $connection->inTransaction();
    }

    /**
     * 提交数据库事务
     *
     * @param string $connection_name 连接名称，如果没有则使用默认数据库连接
     * @return boolean
     */
    public static function commit($connection_name = '')
    {
        $connection = $connection_name 
            ? (ConnectionPool::build())->connection($connection_name)
            : self::defaultConnection();
        return $connection->commit();
    }

    /**
     * 回滚数据库事务
     *
     * @param string $connection_name  连接名称，如果没有则使用默认数据库连接
     * @return boolean
     */
    public static function rollback($connection_name = '')
    {
        $connection = $connection_name 
            ? (ConnectionPool::build())->connection($connection_name)
            : self::defaultConnection();
        return $connection->rollback();
    }

    /**
     * 获取数据库操作日志
     *
     * @param string $connection_name 连接名称，如果没有则使用默认数据库连接
     * @return array
     */
    public static function getQueryLog($connection_name = '')
    {
        $connection = $connection_name 
            ? (ConnectionPool::build())->connection($connection_name)
            : self::defaultConnection();
        return $connection->getQueryLog();
    }

    /**
     * 获取一段原生的SQL
     *
     * @param string $sql
     * @return Expression
     */
    public static function raw($sql)
    {
        return new Expression($sql);
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
