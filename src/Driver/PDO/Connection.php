<?php

namespace Sue\Model\Driver\PDO;

use PDO;
use Sue\Model\Common\DatabaseException;
use Sue\Model\Driver\Contracts\ConnectionInterface;

class Connection implements ConnectionInterface
{
    /** @var PDO $link */
    private $link;

    public function __construct($mixed)
    {
        if (!extension_loaded('PDO')) {
            throw new DatabaseException('PDO extension is required');
        }

        if ($mixed instanceof PDO) {
            $this->link = $mixed;
            return;
        } else {
            $config = $mixed;
            $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';
            $port = isset($config['port']) ? $config['port'] : 3306;
            $dsn = "mysql:dbname={$config['dbname']};host={$config['host']};port={$port};charset={$charset}";
            $base_options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            ];
            $options = isset($config['options'])
                        ? array_merge($base_options, $config['options'])
                        : $base_options;
                        
            $this->link = new PDO($dsn, $config['username'], $config['password'], $options);
        }
    }

    public function query($sql, $params = [])
    {
        $statement = $this->link->prepare($sql);
        $statement->execute($params ?: null);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
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