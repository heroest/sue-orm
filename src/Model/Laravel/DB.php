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
     * 设置数据库驱动类型 (pdo, mysqli, mysql)
     *
     * @param string $driver
     * @return void
     */
    public static function setDrive($driver)
    {
        return Config::set('driver', $driver);
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
     * table
     *
     * @param string $table
     * @param string|null $as
     * @return Query
     */
    public static function table($table, $as = null)
    {
        return (new Query())->table($table, $as);
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
        ConnectionPool::build()->addConnection($connection_name, $mixed);
    }

    /**
     * 开始数据库事务
     *
     * @param string $connection_name 连接名称，如果没有则使用默认数据库连接
     * @return boolean
     */
    public static function beginTransaction($connection_name = '')
    {
        return self::getConnection($connection_name)->beginTransaction();
    }

    /**
     * 检查是否已经处于事务中
     *
     * @param string $connection_name 连接名称，如果没有则使用默认数据库连接
     * @return boolean
     */
    public static function inTransaction($connection_name = '')
    {
        return self::getConnection($connection_name)->inTransaction();
    }

    /**
     * 提交数据库事务
     *
     * @param string $connection_name 连接名称，如果没有则使用默认数据库连接
     * @return boolean
     */
    public static function commit($connection_name = '')
    {
        return self::getConnection($connection_name)->commit();
    }

    /**
     * 回滚数据库事务
     *
     * @param string $connection_name  连接名称，如果没有则使用默认数据库连接
     * @return boolean
     */
    public static function rollback($connection_name = '')
    {
        return self::getConnection($connection_name)->rollback();
    }

    /**
     * 获取数据库操作日志
     *
     * @param string $connection_name 连接名称，如果没有则使用默认数据库连接
     * @return array
     */
    public static function getQueryLog($connection_name = '')
    {
        return self::getConnection($connection_name)->getQueryLog();
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
     * 获取连接或者默认链接
     *
     * @return ConnectionInterface
     */
    private static function getConnection($connection_name = '')
    {
        $connection_name = $connection_name ?: Config::get('default_connection', '');
        $pool = ConnectionPool::build();
        return $pool->connection($connection_name);
    }
}
