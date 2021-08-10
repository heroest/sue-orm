<?php

namespace Sue\LegacyModel\Model\Laravel;

use Exception;
use Closure;
use InvalidArgumentException;
use BadMethodCallException;
use ReflectionMethod;
use Sue\LegacyModel\Common\DatabaseException;
use Sue\LegacyModel\Common\SQLConst;
use Sue\LegacyModel\Common\Config;
use Sue\LegacyModel\Common\Util;
use Sue\LegacyModel\Driver\ConnectionPool;
use Sue\LegacyModel\Driver\Contracts\ConnectionInterface;
use Sue\LegacyModel\Model\Contracts\ComponentInterface;
use Sue\LegacyModel\Model\Component\Where;
use Sue\LegacyModel\model\Component\Join;
use Sue\LegacyModel\Model\Component\Expression;
use Sue\LegacyModel\Model\Component\SetValue;
use Sue\LegacyModel\Model\Component\InsertValue;

/**
 * 数据库查询构造器
 * 
 */
class Query
{
    /** @var ConnectionPool $connectionPool */
    private $connectionPool = null;
    /** @var ConnectionInterface $connection */
    private $connection;

    private $model;
    private $table;

    private $select = [];
    private $where = [];
    private $join = [];
    private $limit = 0;
    private $offset = 0;
    private $orderBy = [];
    private $groupBy = [];


    private $on = [];

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

    /**
     * from
     *
     * @param string $table
     * @param string|null $as
     * @return self
     */
    public function from($table, $as = null)
    {
        $this->table = $as ? "{$table} AS {$as}" : $table;
        return $this;
    }

    /**
     * from
     *
     * @param string $table
     * @param string|null $as
     * @return self
     */
    public function table($table, $as = null)
    {
        return $this->from($table, $as);
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
        $this->abstractWhere(func_get_args(), '', SQLConst::SQL_AND);
        return $this;
    }

    public function orWhere()
    {
        $this->abstractWhere(func_get_args(), '', SQLConst::SQL_OR);
        return $this;
    }

    public function whereColumn($col_a, $op, $col_b, $boolean = SQLConst::SQL_AND)
    {
        $params = [$col_a, $op, new Expression($col_b)];
        $this->abstractWhere($params, '', $boolean);
        return $this;
    }

    public function orWhereColumn($col_a, $op, $col_b)
    {
        return $this->whereColumn($col_a, $op, $col_b, SQLConst::SQL_OR);
    }

    public function whereIn()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_IN, SQLConst::SQL_AND);
        return $this;
    }

    public function orWhereIn()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_IN, SQLConst::SQL_OR);
        return $this;
    }

    public function whereNotIn()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_NOT_IN, SQLConst::SQL_AND);
        return $this;
    }

    public function orWhereNotIn()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_NOT_IN, SQLConst::SQL_OR);
        return $this;
    }

    public function whereBetween()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_BETWEEN, SQLConst::SQL_AND);
        return $this;
    }

    public function orWhereBetween()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_BETWEEN, SQLConst::SQL_OR);
        return $this;
    }

    public function join()
    {
        $this->abstractJoin(func_get_args(), SQLConst::SQL_INNER_JOIN);
        return $this;
    }

    public function leftJoin()
    {
        $this->abstractJoin(func_get_args(), SQLConst::SQL_LEFT_JOIN);
        return $this;
    }

    public function rightJoin()
    {
        $this->abstractJoin(func_get_args(), SQLConst::SQL_RIGHT_JOIN);
        return $this;
    }

    public function on($from, $op, $to, $boolean = SQLConst::SQL_AND)
    {
        if ($this->on) {
            $this->on[] = $boolean;
        }
        $this->on[] = "{$from} {$op} {$to}";
        return $this;
    }

    public function orOn($from, $op, $to)
    {
        return $this->on($from, $op, $to, SQLConst::SQL_OR);
    }

    public function orderBy($col, $direction = 'asc')
    {
        $this->orderBy[] = "{$col} {$direction}";
        return $this;
    }

    /**
     * 重新排序
     *
     * @param string $col
     * @param string $direction
     * @return self
     */
    public function reorder($col, $direction = 'asc')
    {
        $this->orderBy = [];
        return $this->orderBy($col, $direction);
    }

    /**
     * 随机排序/随机权重排序
     *
     * @param string $by_weight
     * @return self
     */
    public function inRandomOrder($by_weight = '')
    {
        $exression = $by_weight 
            ? new Expression("-LOG(1- RAND())/{$by_weight}")
            : new Expression('RAND()');
        return $this->reorder($exression, '');
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

    public function skip($offset)
    {
        return $this->offset($offset);
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

    public function take($limit)
    {
        return $this->limit($limit);
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
        return $result ? array_pop($result) : null;
    }

    /**
     * 更新数据
     *
     * @param array $data
     * @return int|float rows_affected
     */
    public function update(array $data)
    {
        return $this->executeUpdateQuery($data);
    }

    /**
     * 插入数据
     *
     * @param array $data
     * @return string $last_insert_id
     */
    public function insert(array $data)
    {
        $this->executeInsertQuery($data);
        return $this->lastInsertId();
    }

    /**
     * 插入并无视duplicate
     *
     * @param array $data
     * @return string $last_insert_id
     */
    public function insertOrIgnore(array $data)
    {
        $this->executeInsertQuery($data, SQLConst::SQL_INSERT_IGNORE);
        return $this->lastInsertId();
    }

    /**
     * 开启数据库事务
     *
     * @return boolean
     */
    public function beginTransaction()
    {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * 判断是否处于数据库事务中
     *
     * @return boolean
     */
    public function inTransaction()
    {
        return $this->getConnection()->inTransaction();
    }

    /**
     * 事务提交
     *
     * @return boolean
     */
    public function commit()
    {
        return $this->getConnection()->commit();
    }

    /**
     * 事务回滚
     *
     * @return boolean
     */
    public function rollback()
    {
        return $this->getConnection()->rollback();
    }

    public function lastInsertId()
    {
        return $this->getConnection()->lastInsertId();
    }

    public function affectedRows()
    {
        return $this->getConnection()->affectedRows();
    }

    /**
     * 获取一个链接，如果没设置的话获取默认链接
     *
     * @return ConnectionInterface
     */
    private function getConnection()
    {
        if ($this->connection) {
            return $this->connection;
        } else {
            $default = Config::get('default_connection', '');
            return $this->connection = $this->connectionPool->connection($default);
        }
    }

    /**
     * where条件拼接
     *
     * @param array $params
     * @param string $op
     * @param string $boolean
     * @return void
     */
    private function abstractWhere(array $params, $op = '', $boolean = SQLConst::SQL_AND)
    {
        $params = Where::normalizeParams($params, $op);
        if ($this->where and SQLConst::SQL_LEFTP !== end($this->where)) {
            $this->where[] = $boolean;
        }

        $count_params = count($params);
        if (3 === $count_params) {
            list($key, $op, $val) = $params;
            if (null === $val) {
                $this->where[] = new Expression("{$key} {$op} NULL");
            } else {
                $this->where[] = new Where([$op, $key, $val]);
            }
        } elseif (2 === $count_params) {
            list($key, $val) = $params;
            if (null === $val) {
                $this->where[] = new Expression("{$key} IS NULL");
            } else {
                $op = $op ?: '=';
                $this->where[] = new Where([$op, $key, $val]);
            }
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
            } else {
                $this->where[] = new Expression($param);
            }
        }
    }

    private function abstractJoin(array $params, $join_type = '')
    {
        $this->on = [];
        $params = Join::normalizeParams($params, $join_type);
        $joined = array_shift($params);
        
        switch (count($params)) {
            case 3:
                list($from, $op, $to) = $params;
                $this->on($from, $op, $to);
                break;

            case 1:
                $closure = current($params);
                $closure($this);
                break;
        }
        $this->join[$joined] = new Join($joined, $join_type, implode(' ', $this->on));
        $this->on = [];
    }

    private function executeSelectQuery()
    {
        $this->beforeQuery();
        try {
            list($sql, $params) = $this->compileSelectQuery();
            return $this->getConnection()->query($sql, $params);
        } catch (Exception $e) {
            throw $e;
        } finally {
            $this->afterQuery();
        }
    }

    private function compileSelectQuery()
    {
        $components = [];

        //SELECT
        $components[] = SQLConst::SQL_SELECT;
        $components[] = $this->select ? implode(',', $this->select) : '*';

        //FROM
        $components[] = SQLConst::SQL_FROM;
        $components[] = $this->table;
        
        //JOIN
        if ($this->join) {
            $components = array_merge($components, array_values($this->join));
        }

        //Where
        if ($this->where) {
            $components[] = SQLConst::SQL_WHERE;
            $components = array_merge($components, $this->where);
        }
        
        //ORDER
        if ($this->orderBy) {
            $components[] = SQLConst::SQL_ORDER_BY;
            $components[] = implode(',', $this->orderBy);
        }

        //LIMIT
        if ($this->limit) {
            $components[] = SQLConst::SQL_LIMIT;
            $components[] = "{$this->offset},{$this->limit}";
        }

        return $this->assemble($components);
    }

    private function executeUpdateQuery($data)
    {
        $this->beforeQuery();

        $components = [];

        //UPDATE
        $components[] = SQLConst::SQL_UPDATE;
        $components[] = $this->table;

        //SET
        $components[] = new SetValue($data);

        //Where
        if ($this->where) {
            $components[] = SQLConst::SQL_WHERE;
            $components = array_merge($components, $this->where);
        }
        
        //ORDER
        if ($this->orderBy) {
            $components[] = SQLConst::SQL_ORDER_BY;
            $components[] = implode(',', $this->orderBy);
        }

        //LIMIT
        if ($this->limit) {
            $components[] = SQLConst::SQL_LIMIT;
            $components[] = $this->limit;
        }

        list($sql, $params) = $this->assemble($components);
        $this->getConnection()->query($sql, $params);
        return $this->affectedRows = $this->getConnection()->affectedRows();
    }

    /**
     * 执行INSERT请求
     *
     * @param array $data_inserted
     * @param string $duplicate_handle
     * @param array $data_updated
     * @return void
     */
    private function executeInsertQuery(
        array $data_inserted, 
        $duplicate_handle = '', 
        array $data_updated = []
    )
    {
        $components = [];
        $components[] = ($duplicate_handle === SQLConst::SQL_INSERT_IGNORE)
            ? SQLConst::SQL_INSERT_IGNORE
            : SQLConst::SQL_INSERT;
        $components[] = $this->table;
        $components[] = new InsertValue($data_inserted);
        if ($duplicate_handle === SQLConst::SQL_ON_DUPLCATE_KEY_UPDATE) {
            $components[] = new SetValue($data_updated);
        }
        list($sql, $params) = $this->assemble($components);
        $this->getConnection()->query($sql, $params);
    }

    private function executeDeleteQuery()
    {

    }

    public function getQueryLog()
    {
        return $this->getConnection()->getQueryLog();
    }

    private function beforeQuery()
    {
        $this->lastInsertId = '';
        $this->affectedRows = 0;
    }

    private function afterQuery()
    {
        //todo
    }
    
    /**
     * 拼接片段
     *
     * @param array $components
     * @return (string|array)[]
     */
    private function assemble(array $components)
    {
        $chunks = [];
        $params = [];
        foreach ($components as $component) {
            $chunks[] = (string) $component;
            if ($component instanceof ComponentInterface) {
                $params = array_merge($params, $component->values());
            }
        }
        return [Util::implodeWithSpace($chunks), $params];
    }
}
