<?php

namespace Sue\Tests\LegacyModel\ConnectionTest;

use PDO;
use Sue\LegacyModel\Driver\ConnectionPool;
use Sue\Tests\LegacyModel\ConnectionTest\AbstractConnectionTest;

class PdoTest extends AbstractConnectionTest
{
    public static function setUpBeforeClass()
    {
        self::$driverName = 'pdo';
        parent::setUpBeforeClass();
    }

    public function testConnectionWithLink()
    {
        $dbname = self::$dbName;
        $host = self::$dbHost;
        $port = self::$dbPort;
        $charset = self::$charset;
        $items = [
            "mysql:dbname={$dbname}",
            "host={$host}",
            "port={$port}",
            "charset={$charset}"
        ];
        $dsn = implode(';', $items);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $link = new PDO($dsn, self::$dbUsername, self::$dbPassword, $options);
        $pool = ConnectionPool::build();
        $connection = $pool->addConnection($this->getTestName(), $link);
        $this->assertInstanceOf(self::PDO_CONNECTION, $connection, 'PDO link not matched ' . self::$driverName);
    }
}