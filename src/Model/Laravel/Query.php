<?php

namespace Sue\Model\Model\Laravel;

use Sue\Model\Common\SQLConst;
use Sue\Model\Factory\ConnectionPoolFactory;
use Sue\Model\Model\Component\Where;

class Query
{
    private static $queryLog = [];

    private $model;
    private $connection;
    private $table;
    
    private $lastInsertId;
    private $affectedRows;

    private $select = [];
    private $where = [];
    private $join = [];
    private $take = false;
    private $offset = false;

    public function __construct()
    {
    }

    public function connection($connection_name)
    {
        $pool = ConnectionPoolFactory::build();
        $this->connection = $pool->connection($connection_name);
        return $this;
    }

    public function addConnection($connection_name, $mixed)
    {
        $pool = ConnectionPoolFactory::build();
        $this->connection = $pool->addConnection($connection_name, $mixed);
        return $this;
    }

    public function table($table, $as = null)
    {
        $this->table = $as ? "{$table} AS {$as}" : $table;
        return $this;
    }

    public function select()
    {
        foreach (func_get_args() as $param) {
            if (is_array($param)) {
                foreach ($param as $val) {
                    $this->select[] = $val;
                }
            } else {
                $this->select[] = $param;
            }
        }
    }

    public function where()
    {
        $params = func_get_args();
        if ($this->where and SQLConst::SQL_LEFTP !== end($this->where)) {
            $this->where[] = SQLConst::SQL_AND;
        }
        switch (3 === count($params)) {
            $this->where[] = new Where()
        }
    }
}