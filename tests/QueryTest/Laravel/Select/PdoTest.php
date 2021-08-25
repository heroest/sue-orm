<?php

namespace Sue\Tests\LegacyModel\QueryTest\Laravel\Select;

use Sue\Tests\LegacyModel\QueryTest\Laravel\Select\AbstractSelectQueryTest;

class PdoTest extends AbstractSelectQueryTest
{
    public static function setUpBeforeClass()
    {
        self::$driverName = 'pdo';
        parent::setUpBeforeClass();
    }
}