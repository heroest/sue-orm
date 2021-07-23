<?php

namespace Sue\Model\Factory;

use InvalidArgumentException;
use Sue\Model\Common\Config;
use Sue\Model\Driver\Contracts\ConnectionPoolInterface;

class ConnectionPoolFactory
{
    private static $pool = null;

    private function __construct()
    {
    }

    /**
     * 获取诗句哭链接池
     *
     * @return null|ConnectionPoolInterface
     * @throws InvalidArgumentException
     */
    public static function build()
    {
        if (self::$pool) {
            return self::$pool;
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
                return self::$pool = null;

            case 'mysqli':
                return self::$pool = null;

            case 'pdo':
                return self::$pool = null;
        }
        throw new InvalidArgumentException("Unknown database driver: {$driver}");
    }
}
