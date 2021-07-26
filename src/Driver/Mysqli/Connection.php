<?php

namespace Sue\Model\Driver\Mysqli;

use mysqli;
use mysqli_result;
use Sue\Model\Common\DatabaseException;
use Sue\Model\Driver\Contracts\ConnectionInterface;

class Connection implements ConnectionInterface
{
    /** @var mysqli $link */
    private $link;

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

    public function query($sql, $params = [])
    {
        $statement = $this->link->stmt_init($sql);
        $statement->prepare($sql);
        $types = [];
        foreach ($params as $param) {
            if (is_string($param)) {
                $types[] = 's';
            } elseif (is_float($param)) {
                $types[] = 'd';
            } elseif (is_int($param)) {
                $types[] = 'i';
            } else {
                $types[] = 'b';
            }
        }

        array_unshift($params, implode('', $types));
        $result = call_user_func_array([$statement, 'bind_param'], $params);

        if ($statement->errno) {
            throw new DatabaseException($statement->error, $statement->errno);
        } elseif ($result instanceof mysqli_result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return (bool) $result;
        }
    }

    public function beginTransaction()
    {

    }

    public function inTransaction()
    {

    }

    public function commit()
    {

    }

    public function rollback()
    {
        
    }
}