<?php

namespace Sue\Tests\LegacyModel\QueryTest\Laravel\InsertUpdate;

use Sue\Tests\LegacyModel\AbstractTest;
use Sue\LegacyModel\Model\Laravel\DB;

abstract class AbstractInsertUpdateTest extends AbstractTest
{
    public static function setUpBeforeClass()
    {
        $connection = self::buildConnection();
        $connection->query('TRUNCATE TABLE dog');
        $connection->query('ALTER TABLE dog AUTO_INCREMENT = 1');
    }

    public static function tearDownAfterClass()
    {
        $connection = self::buildConnection();
        $connection->query('TRUNCATE TABLE dog');
        $connection->query('ALTER TABLE dog AUTO_INCREMENT = 1');
    }

    public function testInsert()
    {
        $id = DB::table('dog')
            ->insert([
                'name' => '123', 
                'age' => 1, 
                'color' => 'black', 
                'owner_id' => 1
            ]);
        $this->assertEquals(1, $id);

        $first = DB::table('dog')->select(['name', 'color'])->first();
        $this->assertEquals('123', $first['name']); 
        $this->assertEquals('black', $first['color']);
    }

    public function testInsertDuplicate()
    {
        $this->setExpectedExceptionRegExp(
            self::DATABASE_EXCEPTION,
            '/(Fail to)/i'
        );
        DB::table('dog')
            ->insert([
                'name' => '123', 
                'age' => 1, 
                'color' => 'black', 
                'owner_id' => 1
            ]);
    }
}