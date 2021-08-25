<?php

namespace Sue\Tests\LegacyModel;

use PHPUnit_Framework_TestCase;
use Sue\LegacyModel\Driver\ConnectionPool;
use Sue\LegacyModel\Driver\Contracts\ConnectionInterface;

abstract class AbstractTest extends PHPUnit_Framework_TestCase
{
    private static $index = 0;
    protected static $driverName;
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
        self::$connection = self::buildConnection();
    }

    protected function tearDown()
    {
        self::$connection = null;
        $pool = ConnectionPool::build();
        $pool->reset();
    }

    protected static function buildConnection()
    {
        $pool = ConnectionPool::build();
        $pool->setDriver(self::$driverName);
        $config = [
            'host' => self::$dbHost,
            'username' => self::$dbUsername,
            'password' => self::$dbPassword,
            'port' => self::$dbPort,
            'dbname' => self::$dbName,
            'charset' => self::$charset
        ];
        $name = self::getTestName();
        return $pool->addConnection($name, $config);
    }

    public function testDriverMatchConnection()
    {
        $class = '';
        switch (self::$driverName) {
            case 'mysql':
                $class = self::MYSQL_CONNECTION;
                break;

            case 'mysqli':
                $class = self::MYSQLI_CONNECTION;
                break;

            case 'pdo':
                $class = self::PDO_CONNECTION;
                break;
        }
        $this->assertTrue(self::$connection instanceof $class, (self::$driverName . " use {$class}" ));
    }

    /**
     * 获取测试的名字
     *
     * @return void
     */
    protected static function getTestName()
    {
        $driver = self::$driverName;
        return "{$driver}_test_" . ++self::$index;
    }
}
