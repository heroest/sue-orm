<?php

namespace Sue\Tests\LegacyModel;

use PHPUnit_Framework_TestCase;
use Sue\LegacyModel\Common\Config;
use Sue\LegacyModel\Driver\ConnectionPool;

abstract class AbstractTest extends PHPUnit_Framework_TestCase
{
    protected $driverName;
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


    abstract public function testOpenWithLink();

    public function testOpenWithWrongLink()
    {
        $this->setExpectedExceptionRegExp(
            'InvalidArgumentException',
            '/^(Unexpected type of paramenter).*/'
        );
        $pool = ConnectionPool::build();
        $pool->addConnection('_db_fail', fopen('nul', 'w+'));
    }

    public function testOpenWithWrongUsername()
    {
        $this->setExpectedException(self::DATABASE_EXCEPTION);
        $pool = ConnectionPool::build();
        $config = [
            'host' => self::$dbHost,
            'username' => self::$dbUsername . '-fail',
            'password' => self::$dbPassword,
            'charset' => self::$charset,
            'port' => self::$dbPort,
        ];
        $pool->addConnection('_fail_db', $config);
    }

    public function testQuery()
    {
        $result = self::$connection->query('SELECT 1');
        $this->assertTrue(is_array($result));
    }

    protected function setUp()
    {
        Config::set('driver', $this->driverName);
        if (null === self::$connection) {
            $pool = ConnectionPool::build();
            $config = [
                'host' => self::$dbHost,
                'username' => self::$dbUsername,
                'password' => self::$dbPassword,
                'port' => self::$dbPort,
                'dbname' => self::$dbName,
                'charset' => self::$charset
            ];
            $name = $this->driverName . '_test';
            self::$connection = $pool->addConnection($name, $config);
        }
    }

    public static function tearDownAfterClass()
    {
        self::$connection = null;
        $pool = ConnectionPool::build();
        $pool->destroy();
    }
}