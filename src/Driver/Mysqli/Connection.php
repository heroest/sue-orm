<?php

namespace Sue\Model\Driver\Mysqli;

use mysqli;
use mysqli_result;
use Sue\Model\Common\Util;
use Sue\Model\Common\DatabaseException;
use Sue\Model\Driver\Contracts\ConnectionInterface;

class Connection implements ConnectionInterface
{
    /** @var mysqli $link */
    private $link;
    private $queryLog;

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
        call_user_func_array([$statement, 'bind_param'], $ref);
        $statement->execute();

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
    }

    /** @inheritDoc */
    public function inTransaction()
    {
    }

    /** @inheritDoc */
    public function commit()
    {
    }

    /** @inheritDoc */
    public function rollback()
    {
    }

    /** @inheritDoc */
    public function getQueryLog()
    {
    }

    private function appendQueryLog($sql, array $params = [])
    {

    }
}
