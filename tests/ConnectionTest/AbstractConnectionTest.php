<?php

namespace Sue\Tests\LegacyModel\ConnectionTest;

use Sue\LegacyModel\Driver\ConnectionPool;
use Sue\Tests\LegacyModel\AbstractTest;

abstract class AbstractConnectionTest extends AbstractTest
{
    /**
     * 测试已创建的数据库链接来初始化connection
     *
     * @return void
     */
    abstract public function testConnectionWithLink();

    /**
     * 测试有问题的resource
     *
     * @return void
     */
    public function tesConnectionWithWrongLink()
    {
        $this->setExpectedExceptionRegExp(
            'InvalidArgumentException',
            '/^(Unexpected type of paramenter).*/'
        );
        $pool = ConnectionPool::build();
        $pool->addConnection($this->getTestName(), fopen('nul', 'w+'));
    }

    /**
     * 测试错误的数据库账号名密码
     *
     * @return void
     */
    public function testConnectionWithWrongUsername()
    {
        $this->setExpectedException(self::DATABASE_EXCEPTION);
        $pool = ConnectionPool::build();
        $config = [
            'host' => self::$dbHost,
            'username' => self::$dbUsername . '-aabbcc',
            'password' => self::$dbPassword,
            'charset' => self::$charset,
            'port' => self::$dbPort,
        ];
        $pool->addConnection($this->getTestName(), $config);
    }

    /**
     * 测试正常的Query
     *
     * @return void
     */
    public function testSql()
    {
        $result = self::$connection->query('SELECT 1');
        $this->assertTrue(is_array($result));
    }

    /**
     * 测试错误的sql
     *
     * @return void
     */
    public function testInvalidSql()
    {
        $this->setExpectedException(self::DATABASE_EXCEPTION);
        self::$connection->query('FROM TABLE');
    }

    /**
     * 检查获取数据库link类型
     *
     * @return void
     */
    public function testGetLink()
    {
        $link = self::$connection->getLink();
        switch (get_class(self::$connection)) {
            case self::MYSQL_CONNECTION:
                $boolean = is_resource($link) 
                    and false !== stripos(get_resource_type($link), 'mysql');
                $this->assertTrue($boolean);
                break;

            case self::MYSQLI_CONNECTION:
                $this->assertInstanceOf('mysqli', $link);
                break;

            case self::PDO_CONNECTION:
                $this->assertInstanceOf('PDO', $link);
                break;
        }
    }

    /**
     * 检测默认数据库事务状态
     *
     * @return void
     */
    public function testNoTransaction()
    {
        $this->assertFalse(self::$connection->inTransaction());
    }

    /**
     * 测试数据库开启状态
     *
     * @return void
     */
    public function testStartTransaction()
    {
        self::$connection->beginTransaction();
        $this->assertTrue(self::$connection->inTransaction());
    }

    /**
     * 测试重复开启数据库事务
     *
     * @return void
     */
    public function testNestedTransaction()
    {
        $this->setExpectedExceptionRegExp(
            'BadMethodCallException',
            '/(Transaction already)/'
        );
        self::$connection->beginTransaction();
        self::$connection->beginTransaction();
    }

    /**
     * 测试无事务提交
     *
     * @return void
     */
    public function testCommitWithoutTransaction()
    {
        $this->setExpectedExceptionRegExp(
            'BadMethodCallException',
            '/(No transaction found)/'
        );
        self::$connection->commit();
    }

    /**
     * 测试无事务回滚
     *
     * @return void
     */
    public function testRollbackWithoutTransaction()
    {
        $this->setExpectedExceptionRegExp(
            'BadMethodCallException',
            '/(No transaction found)/'
        );
        self::$connection->rollback();
    }

    /**
     * 测试事务提交
     *
     * @return void
     */
    public function testTransactionCommit()
    {
        $this->assertFalse(self::$connection->inTransaction());
        self::$connection->beginTransaction();
        $this->assertTrue(self::$connection->inTransaction());
        self::$connection->commit();
        $this->assertFalse(self::$connection->inTransaction());
    }

    /**
     * 测试事务回滚
     *
     * @return void
     */
    public function testTransactionRollback()
    {
        $this->assertFalse(self::$connection->inTransaction());
        self::$connection->beginTransaction();
        $this->assertTrue(self::$connection->inTransaction());
        self::$connection->rollback();
        $this->assertFalse(self::$connection->inTransaction());
    }

    /**
     * 测试QueryLog
     *
     * @return void
     */
    public function testQueryLog()
    {
        $sql = 'SELECT 1';
        self::$connection->query($sql);
        $sql_2 = 'SELECT 2';
        self::$connection->query($sql_2);
        $log = self::$connection->getQueryLog();
        $this->assertCount(2, $log);
        $first = current($log);
        $this->assertEquals($sql, $first);
    }
}
