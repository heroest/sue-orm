<?php

namespace Sue\LegacyModel\Driver\Mysqli;

use mysqli;
use mysqli_result;
use Exception;
use BadMethodCallException;
use Sue\LegacyModel\Common\Util;
use Sue\LegacyModel\Common\DatabaseException;
use Sue\LegacyModel\Driver\Contracts\ConnectionInterface;

class Connection implements ConnectionInterface
{
    /** @var mysqli $link */
    private $link;
    private $queryLog;
    /** @var boolean $inTransaction */
    private $inTransaction = false;

    public function __construct($mixed)
    {
        if (!extension_loaded('mysqli')) {
            throw new DatabaseException('mysqli extension is required');
        }

        if ($mixed instanceof mysqli) {
            $this->link = $mixed;
            return;
        } else {
            $config = $mixed;
            $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';
            $port = isset($config['port']) ? $config['port'] : 3306;

            $this->link = new mysqli(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['dbname'],
                $port
            );
            $this->link->set_charset($charset);
        }
    }

    /** @inheritDoc */
    public function query($sql, $params = [])
    {
        $statement = $this->link->stmt_init();
        $statement->prepare($sql);
        $types = [];
        $ref = [];
        foreach ($params as $index => $param) {
            if (is_string($param)) {
                $types[] = 's';
            } elseif (is_float($param)) {
                $types[] = 'd';
            } elseif (is_int($param)) {
                $types[] = 'i';
            } else {
                $types[] = 'b';
            }
            $ref[] = &$params[$index];
        }

        array_unshift($ref, implode('', $types));
        try {
            call_user_func_array([$statement, 'bind_param'], $ref);
            $statement->execute();
            $this->appendQueryLog($sql, $params);

            if ($statement->errno) {
                throw new DatabaseException($statement->error, $statement->errno);
            } else {
                $result = $statement->get_result();
                if ($result instanceof mysqli_result) {
                    $fetched_result = $result->fetch_all(MYSQLI_ASSOC);
                    $result->close();
                    return $fetched_result;
                } else {
                    return (bool) $result;
                }
            }
        } catch (Exception $e) {
            $compiled = Util::compileSQL($sql, $params);
            $msg = "Fail to execute: {$compiled}";
            throw new DatabaseException($msg, 907, $e);
        }
    }

    /** @inheritDoc */
    public function lastInsertId()
    {
        return (string) $this->link->insert_id;
    }

    /** @inheritDoc */
    public function affectedRows()
    {
        return $this->link->affected_rows;
    }

    /** @inheritDoc */
    public function beginTransaction()
    {
        if ($this->inTransaction()) {
            throw new BadMethodCallException("Transaction already started");
        }
        $this->appendQueryLog('[BEGIN TRANSACTION]');
        $result = $this->link->begin_transaction();
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
        $this->appendQueryLog('[COMMIT]');
        $result = $this->link->commit();
        $this->inTransaction = false;
        return $result;
    }

    /** @inheritDoc */
    public function rollback()
    {
        if (!$this->inTransaction()) {
            throw new BadMethodCallException("No transaction found");
        }
        $this->appendQueryLog('[ROLLBACK]');
        $result = $this->link->rollback();
        $this->inTransaction = false;
        return $result;
    }

    /** @inheritDoc */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    private function appendQueryLog($sql, array $params = [])
    {
        $this->queryLog[] = Util::compileSQL($sql, $params);
    }
}
