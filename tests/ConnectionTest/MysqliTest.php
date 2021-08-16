<?php

namespace Sue\Tests\LegacyModel\ConnectionTest;

use mysqli;
use Sue\LegacyModel\Driver\ConnectionPool;
use Sue\Tests\LegacyModel\AbstractTest;

class MysqliTest extends AbstractTest
{
    protected $driverName = 'mysqli';

    public function testOpenWithLink()
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
        $connection = $pool->addConnection('db1', $link);
        $this->assertInstanceOf(self::MYSQLI_CONNECTION, $connection);
    }
}