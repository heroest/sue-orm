<?php

namespace Sue\LegacyModel\Driver\Mysql;

use BadMethodCallException;
use InvalidArgumentException;
use Sue\LegacyModel\Common\DatabaseException;
use Sue\LegacyModel\Driver\Contracts\ConnectionInterface;

class Connection implements ConnectionInterface
{
    /** @var resource $link */
    private $link = null;
    private $queryLog = [];
    /** @var boolean $inTransaction */
    private $inTransaction = false;

    public function __construct($mixed)
    {
        if (!extension_loaded('mysql')) {
            throw new DatabaseException('Mysql extension is required');
        }

        if ($this->isMysqlLink($mixed)) {
            $this->link = $mixed;
        } elseif (is_array($mixed)) {
            $config = $mixed;
            $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';
            $port = isset($config['port']) ? $config['port'] : 3306;
            $host = "{$config['host']}:{$port}";
            $this->link = mysql_connect(
                $host,
                $config['username'],
                $config['password'],
                true
            );
            if (!$this->link) {
                $this->throwException();
            }
            mysql_select_db($config['dbname']);
            mysql_query("SET NAMES {$charset} COLLATE utf8mb4_unicode_ci", $this->link);
        } else {
            throw new InvalidArgumentException('Unexpected type of paramenter: ' . gettype($mixed));
        }
    }

    /** @inheritDoc */
    public function query($sql, $params = [])
    {
        foreach ($params as $param) {
            $param = is_string($param)
                ? ("'" . mysql_real_escape_string($param, $this->link) . "'")
                : (string) $param;
            $sql = self::bindParam($sql, $param);
        }

        $this->appendQueryLog($sql);
        if (false === $result = mysql_query($sql, $this->link)) {
            $this->throwException();
        }

        if (true === $result) {
            return $result;
        } else {
            $list = [];
            while ($row = mysql_fetch_assoc($result)) {
                $list[] = $row;
            }
            mysql_free_result($result);
            return $list;
        }
    }

    /** @inheritDoc */
    public function lastInsertId()
    {
        return (string) mysql_insert_id($this->link);
    }

    /** @inheritDoc */
    public function affectedRows()
    {
        return mysql_affected_rows($this->link);
    }

    /** @inheritDoc */
    public function beginTransaction()
    {
        if ($this->inTransaction()) {
            throw new BadMethodCallException("Transaction already started");
        }
        $sql = 'BEGIN;';
        $this->appendQueryLog($sql);
        $result = (bool) $this->query($sql);
        $this->inTransaction = true;
        return $result;
    }

    /** @inheritDoc */
    public function inTransaction()
    {
        return $this->inTransaction;
    }

    /** @inheritDoc */
    public function commit()
    {
        if (!$this->inTransaction()) {
            throw new BadMethodCallException("No transaction found");
        }
        $sql = 'COMMIT;';
        $this->appendQueryLog($sql);
        $result = (bool) $this->query($sql);
        $this->inTransaction = false;
        return $result;
    }

    /** @inheritDoc */
    public function rollback()
    {
        if (!$this->inTransaction()) {
            throw new BadMethodCallException("No transaction found");
        }
        $sql = 'ROLLBACK;';
        $this->appendQueryLog($sql);
        $result = (bool) $this->query($sql);
        $this->inTransaction = false;
        return $result;
    }

    /** @inheritDoc */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    private function appendQueryLog($sql)
    {
        $this->queryLog[] = $sql;
    }

    /**
     * 查询是否是mysql建立的链接
     *
     * @param resource $resource
     * @return boolean
     */
    private function isMysqlLink($resource)
    {
        return is_resource($resource) and false !== stripos(get_resource_type($resource), 'mysql');
    }

    /**
     * 拼接一个参数
     *
     * @param string $sql
     * @param string $param
     * @return string
     */
    private static function bindParam($sql, $param)
    {
        $index = stripos($sql, '?', 0);
        $head = substr($sql, 0, $index);
        $tail = substr($sql, $index + 1);
        return "{$head}{$param}{$tail}";
    }

    /**
     * 抛出异常
     *
     * @return void
     * @throws DatabaseException
     */
    private function throwException()
    {
        if ('' !== $msg = mysql_error($this->link)) {
            $code = mysql_errno($this->link);
        } else {
            $msg = 'Unknown Error';
            $code = 907;
        }
        throw new DatabaseException($msg, $code);
    }
}
