<?php

namespace Sue\Model\Driver\Mysql;

use Sue\Model\Common\DatabaseException;
use Sue\Model\Driver\Contracts\ConnectionInterface;

class Connection implements ConnectionInterface
{
    /** @var resource $link */
    private $link;

    public function __construct($mixed)
    {
        if (!function_exists('mysql_connect')) {
            throw new DatabaseException('Mysql extension is required');
        }

        if (is_resource($mixed)) {
            $this->link = $mixed;
        } else {
            $config = $mixed;
            $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';
            $port = isset($config['port']) ? $config['port'] : 3306;
            $host = "{$config['host']}:{$port}";
            $this->link = mysql_connect(
                $host, 
                $config['username'], 
                $config['passowrd'],
                true
            );
            mysql_query("SET NAMES {$charset} COLLATE utf8mb4_unicode_ci", $this->link);
        }
    }

    public function query($sql, $params = [])
    {
        foreach ($params as $param) {
            $param = is_string($param) 
                    ? ("'" . mysql_real_escape_string($param, $this->link) . "'")
                    : $param;
            $sql = self::bindParam($sql, $param);
        }
        $result = mysql_query($sql, $this->link);
        $list = [];
        while ($row = mysql_fetch_assoc($result)) {
            $list[] = $row;
        }
        return $list;
    }

    private static function bindParam($sql, $param)
    {
        $index = stripos($sql, '?', 0);
        $head = substr($sql, 0, $index);
        $tail = substr($sql, $index + 1);
        return "{$head}{$param}{$tail}";
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