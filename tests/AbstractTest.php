<?php

namespace Sue\Tests\LegacyModel;

use PHPUnit_Framework_TestCase;
use Sue\LegacyModel\Common\Config;
use Sue\LegacyModel\Driver\ConnectionPool;
use Sue\LegacyModel\Driver\Contracts\ConnectionInterface;

abstract class AbstractTest extends PHPUnit_Framework_TestCase
{
    private static $index = 0;
    protected $driverName;
    /** @var ConnectionInterface $connection */
    protected static $connection;

    const MYSQL_CONNECTION = 'Sue\LegacyModel\Driver\Mysql\Connection';
    const MYSQLI_CONNECTION = 'Sue\LegacyModel\Driver\Mysqli\Connection';
    const PDO_CONNECTION = 'Sue\LegacyModel\Driver\PDO\Connection';
    const DATABASE_EXCEPTION = 'Sue\LegacyModel\Common\DatabaseException';

    protected static $dbHost = 'localhost';
    protected static $dbPort = '3306';
    protected static $dbName = 'main';
    protected static $dbUsername = 'root';
    protected static $dbPassword = 'root';
    protected static $charset = 'utf8mb4';

    protected function setUp()
    {
        self::$connection = $this->buildConnection();
    }

    protected function tearDown()
    {
        self::$connection = null;
        $pool = ConnectionPool::build();
        $pool->destroy();
    }

    protected function buildConnection()
    {
        Config::destroy();
        Config::set('driver', $this->driverName);
        $pool = ConnectionPool::build();
        $config = [
            'host' => self::$dbHost,
            'username' => self::$dbUsername,
            'password' => self::$dbPassword,
            'port' => self::$dbPort,
            'dbname' => self::$dbName,
            'charset' => self::$charset
        ];
        $name = $this->getTestName();
        return $pool->addConnection($name, $config);
    }

    /**
     * 获取测试的名字
     *
     * @return void
     */
    protected function getTestName()
    {
        return "{$this->driverName}_test_" . ++self::$index;
    }
}
