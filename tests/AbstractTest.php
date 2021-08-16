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

    /**
     * 测试已创建的数据库链接来初始化connection
     *
     * @return void
     */
    abstract public function testConnectionWithLink();

    public function tesConnectionWithWrongLink()
    {
        $this->setExpectedExceptionRegExp(
            'InvalidArgumentException',
            '/^(Unexpected type of paramenter).*/'
        );
        $pool = ConnectionPool::build();
        $pool->addConnection($this->getTestName(), fopen('nul', 'w+'));
    }

    public function testConnectionWithWrongUsername()
    {
        $this->setExpectedException(self::DATABASE_EXCEPTION);
        $pool = ConnectionPool::build();
        $config = [
            'host' => self::$dbHost,
            'username' => self::$dbUsername . '-aabbcc',
            'password' => self::$dbPassword,
            'charset' => self::$charset,
            'port' => self::$dbPort,
        ];
        $pool->addConnection($this->getTestName(), $config);
    }

    public function testQuery()
    {
        $result = self::$connection->query('SELECT 1');
        $this->assertTrue(is_array($result));
    }

    public function testFailQuery()
    {
        $this->setExpectedException(self::DATABASE_EXCEPTION);
        self::$connection->query('FROM TABLE');
    }

    public function testGetLink()
    {
        $link = self::$connection->getLink();
        $boolean = is_resource($link) 
            || ($link instanceof \mysqli) 
            || ($link instanceof \PDO);
        $this->assertTrue($boolean);
    }

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

    private function buildConnection()
    {
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
        return self::$connection = $pool->addConnection($name, $config);
    }

    /**
     * 获取测试的命子
     *
     * @return void
     */
    protected function getTestName()
    {
        return "{$this->driverName}_test_" . ++self::$index;
    }
}