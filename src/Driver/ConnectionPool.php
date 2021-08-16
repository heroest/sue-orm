<?php

namespace Sue\LegacyModel\Driver;

use InvalidArgumentException;
use Sue\LegacyModel\Common\Config;
use Sue\LegacyModel\Driver\Contracts\ConnectionInterface;

class ConnectionPool
{
    private static $instance = null;
    private $pool = [];
    private $connectionClass = '';

    private function __construct($connection_class)
    {
        $this->connectionClass = $connection_class;
    }

    /**
     * 获取数据库链接池
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public static function build()
    {
        if (self::$instance) {
            return self::$instance;
        }

        if (null !== $driver = Config::get('driver')) {
            $driver = strtolower($driver);
        } else {
            foreach (['PDO', 'mysqli', 'mysql'] as $extension) {
                if (extension_loaded($extension)) {
                    $driver = strtolower($extension);
                    break;
                }
            }
            $driver = $driver ?: '[null]';
        }
        
        switch ($driver) {
            case 'mysql':
                return self::$instance = new self('Sue\LegacyModel\Driver\Mysql\Connection');

            case 'mysqli':
                return self::$instance = new self('Sue\LegacyModel\Driver\Mysqli\Connection');

            case 'pdo':
                return self::$instance = new self('Sue\LegacyModel\Driver\PDO\Connection');
            
            default:
                throw new InvalidArgumentException("Unknown database driver: {$driver}");
        }
    }

    public function hasConnection($connection_name)
    {
        return isset($this->pool[$connection_name]);
    }

    /**
     * 获取一个链接
     *
     * @param string $connection_name
     * @return ConnectionInterface
     */
    public function connection($connection_name)
    {
        if ($this->hasConnection($connection_name)) {
            return $this->pool[$connection_name];
        }
        throw new InvalidArgumentException("Unknown connection name: {$connection_name}");
    }

    /**
     * 添加一个链接并返回这个链接
     *
     * @param string $connection_name
     * @param mixed $mixed
     * @return ConnectionInterface
     */
    public function addConnection($connection_name, $mixed)
    {
        if (isset($this->pool[$connection_name])) {
            return $this->pool[$connection_name];
        }
        $class = $this->connectionClass;
        $connection = $this->pool[$connection_name] = new $class($mixed);
        Config::setnx('default_connection', $connection_name);
        return $connection;
    }

    public function destroy()
    {
        self::$instance = null;
        $this->pool = [];
        $this->connectionClass = '';
    }
}
