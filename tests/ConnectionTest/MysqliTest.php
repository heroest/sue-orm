<?php

namespace Sue\Tests\LegacyModel\ConnectionTest;

use mysqli;
use Sue\LegacyModel\Driver\ConnectionPool;
use Sue\Tests\LegacyModel\ConnectionTest\AbstractConnectionTest;

class MysqliTest extends AbstractConnectionTest
{
    public static function setUpBeforeClass()
    {
        self::$driverName = 'mysqli';
        parent::setUpBeforeClass();
    }

    public function testConnectionWithLink()
    {
        $link = new mysqli(
            self::$dbHost,
            self::$dbUsername,
            self::$dbPassword,
            self::$dbName,
            self::$dbPort
        );
        $link->set_charset(self::$charset);
        $pool = ConnectionPool::build();
        $connection = $pool->addConnection($this->getTestName(), $link);
        $this->assertInstanceOf(self::MYSQLI_CONNECTION, $connection, 'mysqli link not matched: ' . self::$driverName);
    }
}
