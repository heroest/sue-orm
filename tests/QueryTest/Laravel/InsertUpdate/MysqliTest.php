<?php

namespace Sue\Tests\LegacyModel\QueryTest\Laravel\InsertUpdate;

use Sue\Tests\LegacyModel\QueryTest\Laravel\InsertUpdate\AbstractInsertUpdateTest;

class MysqliTest extends AbstractInsertUpdateTest
{
    public static function setUpBeforeClass()
    {
        self::$driverName = 'mysqli';
        parent::setUpBeforeClass();
    }
}