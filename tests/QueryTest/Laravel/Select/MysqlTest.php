<?php

namespace Sue\Tests\LegacyModel\QueryTest\Laravel\Select;

use Sue\Tests\LegacyModel\QueryTest\Laravel\Select\AbstractSelectQueryTest;

class MysqlTest extends AbstractSelectQueryTest
{
    public static function setUpBeforeClass()
    {
        self::$driverName = 'mysql';
        parent::setUpBeforeClass();
    }
}