<?php

namespace Sue\Tests\LegacyModel\QueryTest;

use Sue\Tests\LegacyModel\AbstractTest;
use Sue\LegacyModel\Model\Laravel\Query;
use Sue\LegacyModel\Model\Laravel\DB;

abstract class AbstractQueryTest extends AbstractTest
{
    protected $driverName = '';

    public function testFirstRow()
    {
        $row = (new Query())
            ->table('user')
            ->first();
        $this->assertEquals($row['age'], 11);
    }

    public function testDBFirstRow()
    {
        $db_row = DB::table('user')
            ->where('name', 'aaa')
            ->first();

        $row = (new Query())
            ->table('user')
            ->first();
        $this->assertEquals($row['age'], $db_row['age']);
    }

    public function testFromNonExistsTable()
    {
        $this->setExpectedExceptionRegExp(
            self::DATABASE_EXCEPTION,
            "/(Fail to execute)/"
        );
        DB::table('table_not_exists')->first();
    }

    public function testTableAlias()
    {
        $row = DB::table('user', 'u')
            ->select(['u.name'])
            ->orderBy('id', 'ASC')
            ->first();
        $this->assertEquals($row['name'], 'aaa');
    }

    public function testWrongTableAlias()
    {
        $this->setExpectedExceptionRegExp(
            self::DATABASE_EXCEPTION,
            "/(Fail to execute)/"
        );
        DB::table('user', 'u')->select(['fff.name'])->first();
    }

    public function testSelectDefault()
    {
        $row = DB::table('user')->first();
        $this->assertEquals(5, count(array_keys($row)));
    }

    public function testSelectArray()
    {
        $select = ['name', 'age'];
        $row = DB::table('user')->select($select)->first();
        $this->assertEquals(2, count(array_keys($row)));
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('age', $row);
    }

    public function testSelectParams()
    {
        $row = DB::table('user')->select('name', 'age')->first();
        $this->assertEquals(2, count(array_keys($row)));
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('age', $row);
    }

    public function testCount()
    {
        $count = DB::table('user')->count();
        $this->assertEquals(5, $count);
    }

    public function testCountWithWhere()
    {
        $count = DB::table('user')->where('name', 'aaa')->count();
        $this->assertEquals(1, $count);
    }

    public function testMax()
    {
        $max = DB::table('user')->max('age');
        $this->assertEquals(99, $max);
    }

    public function testMaxWithWhere()
    {
        $max = DB::table('user')->where('age', '<', 40)->max('age');
        $this->assertEquals(31, $max);
    }

    public function testMin()
    {
        $min = DB::table('user')->min('age');
        $this->assertEquals(11, $min);
    }

    public function testMinWithWhere()
    {
        $min = DB::table('user')->where('age', '>=', 30)->min('age');
        $this->assertEquals(31, $min);
    }

    public function testAvg()
    {
        $avg = DB::table('user')->avg('age');
        $this->assertEquals(40.6, $avg);
    }

    public function testAvgWithWhere()
    {
        $avg = DB::table('user')->where('age', '<', 30)->avg('age');
        $this->assertEquals(16, $avg);
    }

    public function testSum()
    {
        $sum = DB::table('user')->sum('age');
        $this->assertEquals(203, $sum);
    }

    public function testSumWithWhere()
    {
        $sum = DB::table('user')->where('age', '>', 32)->sum('age');
        $this->assertEquals(140, $sum);
    }

    public function testWhere()
    {
        $row = DB::table('user')->where('name', 'aaa')->first();
        $this->assertEquals(11, $row['age']);
    }

    public function testWhereEquals()
    {
        $row = DB::table('user')->where('name', '=', 'aaa')->first();
        $this->assertEquals(11, $row['age']);
    }

    public function testWhereGreaterThan()
    {
        $row = DB::table('user')->where('age', '>', 11)->first();
        $this->assertEquals(21, $row['age']);
    }

    public function testWhereGreaterEqualThan()
    {
        $row = DB::table('user')->where('age', '>=', 11)->first();
        $this->assertEquals(11, $row['age']);
    }

    public function testWhereLike()
    {
        $row = DB::table('user')->where('name', 'LIKE', '%a%')->first();
        $this->assertEquals(1, $row['id']);
    }

    public function testWhereNotLike()
    {
        $row = DB::table('user')->where('age', 'NOT LIKE', '%1')->first();
        $this->assertEquals(5, $row['id']);
    }

    public function testOrWhere()
    {
        $list = DB::table('user')
            ->where('age', '=', 11)
            ->orWhere('name', 'ccc')
            ->get();
        $this->assertCount(2, $list);
        list($a, $b) = $list;
        $this->assertEquals('aaa', $a['name']);
        $this->assertEquals(31, $b['age']);
    }

    public function testWhereArray()
    {
        $row = DB::table('user')->where([['name', 'aaa']])->first();
        $this->assertEquals(11, $row['age']);
    }

    public function testWhere2DArray()
    {
        $row = DB::table('user')
            ->where([
                ['name', 'aaa'],
                ['age', 11]
            ])->first();
        $this->assertEquals(1, $row['id']);
    }

    public function testWhere2DArrayFalse()
    {
        $row = DB::table('user')
            ->where([
                ['name', 'aaa'],
                ['age', 15]
            ])->first();
        $this->assertNull($row);
    }

    public function testWhereClosure()
    {
        $row = DB::table('user')->where(function ($q) {
            $q->where('name', 'bbb');
        })->first();
        $this->assertEquals(21, $row['age']);
    }

    public function testWhereNestedClosure()
    {
        $data = DB::table('user')
            ->where(function ($q) {
                $q->where('name', 'bbb')
                    ->orWhere(function ($q) {
                        $q->where('age', 11);
                    });
            })->get();
        $this->assertCount(2, $data);
        list($a, $b) = $data;
        $this->assertEquals('aaa', $a['name']);
        $this->assertEquals(21, $b['age']);
    }

    public function testWhereMultiple()
    {
        $row = DB::table('user')
            ->where('name', 'aaa')
            ->where('age', 11)
            ->first();
        $this->assertNotEquals(null, $row);
        $this->assertArrayHasKey('id', $row);
        $this->assertEquals(1, $row['id']);
    }

    public function testWhereMultipleFalse()
    {
        $row = DB::table('user')
            ->where('name', 'aaa')
            ->where('age', 21)
            ->first();
        $this->assertNull($row);
    }

    public function testWhereMultipleGetFalse()
    {
        $data = DB::table('user')
            ->where('name', 'aaa')
            ->where('age', 21)
            ->get();
        $this->assertCount(0, $data);
    }

    public function testWhereColumn()
    {
        $row = DB::table('user')->whereColumn('name', '=', 'age')->first();
        $this->assertEquals(5, $row['id']);
    }

    public function testWhereIn()
    {
        $data = DB::table('user')->whereIn('age', [11, 21, 31, 0])->get();
        $this->assertCount(3, $data);
        list($a, $b, $c) = $data;
        $this->assertEquals('aaa', $a['name']);
        $this->assertEquals('bbb', $b['name']);
        $this->assertEquals('ccc', $c['name']);
    }

    public function testNotWhereIn()
    {
        $data = DB::table('user')->whereNotIn('age', [11, 21, 41, 0])->get();
        $this->assertCount(2, $data);
    }

    public function testWhereBetween()
    {
        $data = DB::table('user')->whereBetween('age', [31, 41])->get();
        list($a, $b) = $data;
        $this->assertEquals(3, $a['id']);
        $this->assertEquals(4, $b['id']);
    }

    public function testWhereNotBetween()
    {
        $data = DB::table('user')->whereNotBetween('age', [21, 41])->get();
        list($a, $b) = $data;
        $this->assertEquals(1, $a['id']);
        $this->assertEquals(5, $b['id']);
    }
}