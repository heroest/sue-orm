<?php

namespace Sue\Model\Driver;

use InvalidArgumentException;
use Sue\Model\Common\Config;
use Sue\Model\Driver\Contracts\ConnectionInterface;

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
     * 获取诗句哭链接池
     *
     * @return null|ConnectionPoolInterface
     * @throws InvalidArgumentException
     */
    public static function build()
    {
        if (self::$instance) {
            return self::$instance;
        }

        $config = Config::getInstance();
        if (null !== $driver = $config->get('driver')) {
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
                return self::$instance = new self('Sue\Model\Driver\Mysql\Connection');

            case 'mysqli':
                return self::$instance = new self('Sue\Model\Driver\Mysqli\Connection');

            case 'pdo':
                return self::$instance = new self('Sue\Model\Driver\PDO\Connection');
            
            default:
                throw new InvalidArgumentException("Unknown database driver: {$driver}");
        }
    }

    /**
     * 获取一个链接
     *
     * @param string $connection_name
     * @return ConnectionInterface
     */
    public function connection($connection_name)
    {
        if (isset($this->pool[$connection_name])) {
            return $this->pool[$connection_name];
        }
        throw new InvalidArgumentException("Unknown connection name: {$connection_name}");
    }

    /**
     * 添加一个链接并立即返回
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
        return $this->pool[$connection_name] = new $class($mixed);
    }
}
