<?php

namespace Sue\LegacyModel\Driver;

use InvalidArgumentException;
use Sue\LegacyModel\Common\Config;
use BadMethodCallException;
use Sue\LegacyModel\Driver\Contracts\ConnectionInterface;

class ConnectionPool
{
    private static $instance = null;
    private $pool = [];
    private $connectionClass = '';
    private $defaultConnection = null;
    private $driver = '[null]';

    private function __construct()
    {
    }

    /**
     * 获取数据库链接池
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public static function build()
    {
        return self::$instance ?: self::$instance = new self();
    }

    public function setDriver($driver)
    {
        if ($this->connectionClass) {
            return false;
        } elseif (null !== $driver) {
            $this->driver = strtolower($driver);
        } else {
            foreach (['PDO', 'mysqli', 'mysql'] as $extension) {
                if (extension_loaded($extension)) {
                    $this->driver = strtolower($extension);
                    break;
                }
            }
        }
        
        switch ($this->driver) {
            case 'mysql':
                $this->connectionClass = 'Sue\LegacyModel\Driver\Mysql\Connection';
                break;

            case 'mysqli':
                $this->connectionClass = 'Sue\LegacyModel\Driver\Mysqli\Connection';
                break;

            case 'pdo':
                $this->connectionClass = 'Sue\LegacyModel\Driver\PDO\Connection';
                break;
            
            default:
                throw new InvalidArgumentException("Unknown database driver: {$driver}");
        }
        return true;
    }

    public function getDriver()
    {
        return $this->driver;
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
        if (!$this->defaultConnection) {
            $this->defaultConnection = $connection;
        }
        return $connection;
    }

    /**
     * 获取一个默认链接（第一个建立的链接)
     *
     * @return ConnectionInterface
     * @throws BadMethodCallException
     */
    public function getDefaultConnection()
    {
        if ($this->defaultConnection) {
            return $this->defaultConnection;
        } else {
            throw new BadMethodCallException('No connection found');
        }
    }

    public function reset()
    {
        $this->pool = [];
        $this->connectionClass = '';
        $this->defaultConnection = null;
        $this->driverName = '';
    }
}
