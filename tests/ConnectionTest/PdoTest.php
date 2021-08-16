<?php

namespace Sue\Tests\LegacyModel\ConnectionTest;

use PDO;
use Sue\LegacyModel\Driver\ConnectionPool;
use Sue\Tests\LegacyModel\AbstractTest;

class PdoTest extends AbstractTest
{
    protected $driverName = 'pdo';

    public function testOpenWithLink()
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
        $connection = $pool->addConnection('db1', $link);
        $this->assertInstanceOf(self::PDO_CONNECTION, $connection);
    }
}