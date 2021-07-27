<?php

namespace Sue\Model\Driver\Contracts;

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
     *
     * @return boolean
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
     */
    public function commit();

    /**
     * 回滚事务
     *
     * @return void
     */
    public function rollback();

    /**
     * 获取SQL日志
     *
     * @return array
     */
    public function getQueryLog();
}