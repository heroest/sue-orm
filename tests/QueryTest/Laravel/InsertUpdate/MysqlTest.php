<?php

namespace Sue\Tests\LegacyModel\QueryTest\Laravel\InsertUpdate;

use Sue\Tests\LegacyModel\QueryTest\Laravel\InsertUpdate\AbstractInsertUpdateTest;

class MysqlTest extends AbstractInsertUpdateTest
{
    public static function setUpBeforeClass()
    {
        self::$driverName = 'mysql';
        parent::setUpBeforeClass();
    }
}