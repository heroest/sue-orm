<?php

namespace Sue\Model\Model\Laravel;

use Exception;
use Closure;
use InvalidArgumentException;
use ReflectionMethod;
use Sue\Model\Common\DatabaseException;
use Sue\Model\Common\SQLConst;
use Sue\Model\Driver\ConnectionPool;
use Sue\Model\Driver\Contracts\ConnectionInterface;
use Sue\Model\Model\Contracts\ComponentInterface;
use Sue\Model\Model\Component\Where;

class Query
{
    private static $queryLog = [];
    /** @var ConnectionPool $connectionPool */
    private $connectionPool = null;

    private $model;
    /** @var ConnectionInterface $connection */
    private $connection;
    private $table;

    private $lastInsertId;
    private $affectedRows;

    private $select = [];
    private $where = [];
    private $join = [];
    private $limit = 0;
    private $offset = 0;

    public function __construct()
    {
        $this->connectionPool = ConnectionPool::build();
    }

    public function connection($connection_name)
    {
        $this->connection = $this->connectionPool->connection($connection_name);
        return $this;
    }

    public function addConnection($connection_name, $mixed)
    {
        $this->connection = $this->connectionPool->addConnection($connection_name, $mixed);
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
        return $this;
    }

    public function where()
    {
        $this->abstractWhere(func_get_args(), false, SQLConst::SQL_AND);
        return $this;
    }

    public function orWhere()
    {
        $this->abstractWhere(func_get_args(), false, SQLConst::SQL_OR);
        return $this;
    }

    public function whereIn()
    {
        $this->abstractWhere(func_get_args(), 'IN', SQLConst::SQL_AND);
    }

    public function orWhereIn()
    {
        $this->abstractWhere(func_get_args(), 'IN', SQLConst::SQL_OR);
    }

    public function whereNotIn()
    {

    }

    public function orWhereNotIn()
    {

    }

    public function offset($offset)
    {
        $offset = (int) $offset;
        if ($offset >= 0) {
            $this->offset = $offset;
        } else {
            throw new InvalidArgumentException("Invalid offset: {$offset}");
        }
    }

    public function limit($limit)
    {
        $limit = (int) $limit;
        if ($limit > 0) {
            $this->limit = $limit;
        } else {
            throw new InvalidArgumentException("Invalid limit: {$limit}");
        }
    }

    /**
     * 查询批量数据
     *
     * @return array
     */
    public function get()
    {
        return $this->executeSelectQuery();        
    }

    /**
     * 查询一条数据
     *
     * @return null|array
     */
    public function first()
    {
        $this->offset(0);
        $this->limit(1);
        $result = $this->executeSelectQuery();
        return $result ? null : array_pop($result);
    }

    private function abstractWhere(array $params, $op = false, $word = SQLConst::SQL_AND)
    {
        if ($this->where and SQLConst::SQL_LEFTP !== end($this->where)) {
            $this->where[] = $word;
        }

        $count_params = count($params);
        if (3 === $count_params) {
            list($key, $op, $val) = $params;
            $this->where[] = new Where([$op, $key, $val]);
        } elseif (2 === $count_params) {
            list($key, $val) = $params;
            $op = (false !== $op)
                    ? $op
                    : (null === $val ? 'IS' : '=');
            $this->where[] = new Where([$op, $key, $val]);
        } elseif (1 === $count_params) {
            $param = $params[0];
            if ($param instanceof Closure) {
                $this->where[] = SQLConst::SQL_LEFTP;
                $param($this);
                $this->where[] = SQLConst::SQL_RIGHTP;
            } elseif (is_array($param)) {
                foreach ($param as $condition) {
                    call_user_func_array([$this, 'where'], $condition);
                }
            }
        } else {
            throw new InvalidArgumentException('Invalid number of parameters');
        }
    }

    private function executeSelectQuery()
    {
        $components = [];

        //SELECT
        $components[] = 'SELECT';
        $components[] = $this->select ? implode(',', $this->select) : '*';

        //FROM
        $components[] = "FROM {$this->table}";
        
        //JOIN

        //Where
        $components[] = 'WHERE';
        $components = array_merge($components, $this->where);
        
        //ORDER

        //LIMITE
        if ($this->limit) {
            $components[] = "LIMIT {$this->offset},{$this->limit}";
        }

        list($sql, $params) = $this->compile($components);

        $raw = $this->getRawSQL($sql, $params);
        try {
            return $this->connection->query($sql, $params);
        } catch (Exception $e) {
            throw new DatabaseException("Fail to execute SQL: {$raw}", 907, $e);
        } finally {
            self::$queryLog[] = $raw;
        }
    }

    public function getQueryLog()
    {
        return self::$queryLog;
    }

    private function compile(array $components)
    {
        $chunks = [];
        $params = [];
        foreach ($components as $component) {
            $chunks[] = trim(strval($component));
            if ($component instanceof ComponentInterface) {
                $params = array_merge($params, $component->values());
            }
        }
        return [implode(' ', $chunks), $params];
    }

    /**
     * 返回执行的SQL
     *
     * @param string $sql
     * @param array $params
     * @return string
     */
    private function getRawSQL($sql, $params)
    {
        $line = $sql;
        foreach ($params as $param) {
            if (null === $param) {
                $param = 'null';
            } elseif (is_int($param)) {
                $param = (int) $param;
            } else {
                $param = (string) $param;
                $param = "'{$param}'";
            }

            $index = stripos($line, '?', 0);
            $head = substr($line, 0, $index);
            $tail = substr($line, $index + 1);
            $line = "{$head}{$param}{$tail}";
        }
        return $line;
    }
}
