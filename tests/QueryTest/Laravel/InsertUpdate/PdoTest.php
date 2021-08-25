<?php

namespace Sue\Tests\LegacyModel\QueryTest\Laravel\InsertUpdate;

use Sue\Tests\LegacyModel\QueryTest\Laravel\InsertUpdate\AbstractInsertUpdateTest;

class PdoTest extends AbstractInsertUpdateTest
{
    public static function setUpBeforeClass()
    {
        self::$driverName = 'pdo';
        parent::setUpBeforeClass();
    }
}