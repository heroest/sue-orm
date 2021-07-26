<?php

namespace Sue\Model\Driver\Contracts;

interface ConnectionInterface
{
    /**
     * 查询
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function query($sql, $params = []);

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
}