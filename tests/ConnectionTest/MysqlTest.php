<?php

namespace Sue\Tests\LegacyModel\ConnectionTest;

use Sue\LegacyModel\Driver\ConnectionPool;
use Sue\Tests\LegacyModel\AbstractTest;


class MysqlTest extends AbstractTest
{
    protected $driverName = 'mysql';

    public function testConnectionWithLink()
    {
        $host = self::$dbHost . ':' . self::$dbPort;
        $link = mysql_connect(
            $host,
            self::$dbUsername,
            self::$dbPassword,
            true
        );
        mysql_select_db(self::$dbName);
        $charset = self::$charset;
        mysql_query("SET NAMES {$charset} COLLATE utf8mb4_unicode_ci", $link);
        $pool = ConnectionPool::build();
        $connection = $pool->addConnection($this->getTestName(), $link);
        $this->assertInstanceOf(self::MYSQL_CONNECTION, $connection);
    }
} 