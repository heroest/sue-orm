<?php

namespace Sue\LegacyModel\Driver\Contracts;

interface ConnectionInterface
{
    /**
     * 查询
     *
     * @param string $sql
     * @param array $params
     * @return array|boolean
     */
    public function query($sql, $params = []);

    /**
     * 获取最后一次插入的id
     *
     * @return string
     */
    public function lastInsertId();

    /**
     * 获取修改行数
     *
     * @return int|float
     */
    public function affectedRows();

    /**
     * 开启数据库事务
     * 如果已处于事务中会抛出异常
     *
     * @return boolean
     * @throws \BadMethodCallException
     */
    public function beginTransaction();

    /**
     * 是否处于数据库事务
     *
     * @return boolean
     */
    public function inTransaction();

    /**
     * 提交事务
     *
     * @return boolean
     * @throws \BadMethodCallException
     */
    public function commit();

    /**
     * 回滚事务
     *
     * @return void
     * @throws \BadMethodCallException
     */
    public function rollback();

    /**
     * 获取SQL日志
     *
     * @return array
     */
    public function getQueryLog();

    /**
     * 获取实际的数据库链接
     *
     * @return \mysqi|\PDO|resource
     */
    public function getLink();
}