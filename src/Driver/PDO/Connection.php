<?php

namespace Sue\LegacyModel\Driver\PDO;

use PDO;
use Exception;
use BadMethodCallException;
use InvalidArgumentException;
use Sue\LegacyModel\Common\DatabaseException;
use Sue\LegacyModel\Driver\Contracts\ConnectionInterface;
use Sue\LegacyModel\Common\Util;

class Connection implements ConnectionInterface
{
    /** @var PDO $link */
    private $link;
    private $queryLog = [];
    private $affectedRows = 0;

    public function __construct($mixed)
    {
        if (!extension_loaded('PDO')) {
            throw new DatabaseException('PDO extension is required');
        }

        if ($mixed instanceof PDO) {
            $this->link = $mixed;
            return;
        } elseif (is_array($mixed)) {
            $config = $mixed;
            $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';
            $port = isset($config['port']) ? $config['port'] : 3306;
            $items = [
                "mysql:dbname={$config['dbname']}",
                "host={$config['host']}",
                "port={$port}",
                "charset={$charset}"
            ];
            $dsn = implode(';', $items);
            $base_options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];
            $options = isset($config['options'])
                ? array_merge($base_options, $config['options'])
                : $base_options;
            $this->link = new PDO($dsn, $config['username'], $config['password'], $options);
        } else {
            throw new InvalidArgumentException('Unexpected type of paramenter: ' . gettype($mixed));
        }
    }

    /** @inheritDoc */
    public function query($sql, $params = [])
    {
        try {
            $this->affectedRows = 0;
            $statement = $this->link->prepare($sql);
            $statement->execute($params ?: null);
            $this->appendQueryLog($sql, $params);
            $this->affectedRows = $statement->rowCount();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $compiled = Util::compileSQL($sql, $params);
            $msg = "Fail to execute: {$compiled}";
            throw new DatabaseException($msg, 907, $e);
        }
    }

    /** @inheritDoc */
    public function lastInsertId()
    {
        return (string) $this->link->lastInsertId();
    }

    /** @inheritDoc */
    public function affectedRows()
    {
        return $this->affectedRows;
    }

    /** @inheritDoc */
    public function beginTransaction()
    {
        if ($this->inTransaction()) {
            throw new BadMethodCallException("Transaction already started");
        }
        $this->appendQueryLog('[BEGIN TRNASACTION]');
        return $this->link->beginTransaction();
    }

    /** @inheritDoc */
    public function inTransaction()
    {
        return $this->link->inTransaction();
    }

    /** @inheritDoc */
    public function commit()
    {
        if (!$this->inTransaction()) {
            throw new BadMethodCallException("No transaction found");
        }
        $this->appendQueryLog('[COMMIT]');
        return $this->link->commit();
    }

    /** @inheritDoc */
    public function rollback()
    {
        if (!$this->inTransaction()) {
            throw new BadMethodCallException("No transaction found");
        }
        $this->appendQueryLog('[ROLLBACK]');
        return $this->link->rollback();
    }

    /** @inheritDoc */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    /**
     * 添加数据库日志
     *
     * @param string $sql
     * @param array $params
     * @return void
     */
    private function appendQueryLog($sql, $params = [])
    {
        $this->queryLog[] = ($params)
            ? Util::compileSQL($sql, $params)
            : $sql;
    }
}
