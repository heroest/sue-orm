<?php

namespace Sue\LegacyModel\Model\Laravel;

use Exception;
use Closure;
use InvalidArgumentException;
use BadMethodCallException;
use ReflectionMethod;
use Sue\LegacyModel\Common\DatabaseException;
use Sue\LegacyModel\Common\SQLConst;
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
    private $connection = null;
    private $modelClass = '';

    private $table = '';
    private $select = [];
    private $aggregate = '';
    private $where = [];
    private $join = [];
    private $limit = 0;
    private $offset = 0;
    private $orderBy = [];
    private $groupBy = [];
    private $on = [];
    private $lock = '';

    public function __construct($model_class = '')
    {
        $this->connectionPool = ConnectionPool::build();
        $this->modelClass = $model_class;
    }

    /**
     * 设置数据库链接
     *
     * @param string $connection_name
     * @return self
     */
    public function connection($connection_name)
    {
        $this->connection = $this->connectionPool->connection($connection_name);
        return $this;
    }

    /**
     * 添加一条数据库链接
     *
     * @param string $connection_name
     * @param mixed $mixed
     * @return self
     */
    public function addConnection($connection_name, $mixed)
    {
        $this->connection = $this->connectionPool->addConnection($connection_name, $mixed);
        return $this;
    }

    /**
     * 获取执行过的SQL
     *
     * @return array
     */
    public function getQueryLog()
    {
        return $this->getConnection()->getQueryLog();
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

    /**
     * SELECT， 如果有聚合会无视这里
     *
     * @return self
     */
    public function select()
    {
        $params = func_get_args();
        $params = is_array($params[0]) ? $params[0] : $params;
        foreach ($params as $item) {
            if (!in_array($item, $this->select)) {
                $this->select[] = $item;
            }
        }
        return $this;
    }

    /**
     * Count
     *
     * @param string $column
     * @return int|float
     */
    public function count($column = '*')
    {
        return $this->selectAggregate("COUNT({$column})");
    }

    /**
     * MAX
     *
     * @param string $column
     * @return int|float|string
     */
    public function max($column)
    {
        return $this->selectAggregate("MAX({$column})");
    }

    /**
     * MIN
     *
     * @param string $column
     * @return int|float|string
     */
    public function min($column = '*')
    {
        return $this->selectAggregate("MIN({$column})");
    }

    /**
     * AVERAGE
     *
     * @param string $column
     * @return int|float
     */
    public function avg($column)
    {
        return $this->selectAggregate("AVG({$column})");
    }

    /**
     * SUM
     *
     * @param string $column
     * @return int|float
     */
    public function sum($column)
    {
        return $this->selectAggregate("SUM({$column})");
    }

    /**
     * 基础where条件
     *
     * @return self
     * ```php
     * $query->where('age', 18); // WHERE age = 18;
     * $query->where('age', '=', 18); // WHERE age = 18;
     * 
     * $query->where(['age', 18]); //WHERE age = 18
     * $query->where(['age', '=', 18]); //where age = 18
     * $query->where([['age', 18], ['id', 1]]); //where age = 18 AND id = 1
     * $query->where(['age' => 18]); // WHERE age = 18
     * 
     * $query->where(function (Query $q) {
     *      $q->where('age', '=', 18);
     * }); // WHERE age = 18
     * 
     * $query->where('name', 'LIKE', 'Sue%'); //WHERE name LIKE 'Sue%'
     * 
     * $query->where(DB::raw("LENGTH('name') > 4")); //WHERE LENGTH('name') > 4
     * ```
     */
    public function where()
    {
        $this->abstractWhere(func_get_args(), '', SQLConst::SQL_AND);
        return $this;
    }

    /**
     * orWhere, 用法同where()，链式调用时会使用OR拼接
     * 
     * @see where()
     * @return self
     */
    public function orWhere()
    {
        $this->abstractWhere(func_get_args(), '', SQLConst::SQL_OR);
        return $this;
    }

    /**
     * WHERE EXIST
     * 
     * @param \Closure|Query $mixed
     * 
     * @return self
     * 
     * ```php
     * $query
     *      ->table('parent')
     *      ->whereExist(function (Query $q) {
     *          $q->table('child')->whereColumn('child.parent_id', '=', 'parent.id');
     *      })->get();
     * 
     * 
     * $sub_query = DB::table('child')->whereColumn('child.parent_id', '=', 'parent.id');
     * $query
     *      ->table('parent')
     *      ->whereExist($sub_query)
     *      ->get();
     * ```
     */
    public function whereExist()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_EXISTS, SQLConst::SQL_AND);
        return $this;
    }

    /**
     * orWhereExist用法同whereExist，链式调用时会使用OR拼接
     * 
     * @param \Closure|Query $mixed
     * 
     * @return self
     */
    public function orWhereExist()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_EXISTS, SQLConst::SQL_OR);
        return $this;
    }

    /**
     * whereNotExist用法同whereExist
    * 
     * @param \Closure|Query $mixed
     * 
     * @return self
     */
    public function whereNotExist()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_NOT_EXISTS, SQLConst::SQL_AND);
        return $this;
    }

    /**
     * orWhereNotExist用法同whereExists, 链式调用时会使用OR拼接
     * 
     * @param \Closure|Query $mixed
     * 
     * @return self
     */
    public function orWhereNotExist()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_NOT_EXISTS, SQLConst::SQL_OR);
        return $this;
    }

    /**
     * where-column
     *
     * @param string $col_a
     * @param string $op
     * @param string $col_b
     * @param string $boolean
     * @return self
     * 
     * ```php
     * //WHERE user.created_time = user.updated_time
     * $query->table('user')->whereColumn('user.created_time', '=', 'user.updated_time');
     * 
     * //WHERE user.created_time < user.updated_time
     * $query->table('user')->whereColumn('user.created_time', '<', 'user.updated_time');
     * ```
     */
    public function whereColumn($col_a, $op, $col_b, $boolean = SQLConst::SQL_AND)
    {
        $params = [$col_a, $op, new Expression($col_b)];
        $this->abstractWhere($params, '', $boolean);
        return $this;
    }

    /**
     * orWhereColumn，用法同whereColumn,链式调用时会使用OR拼接
     *
     * @param string $col_a
     * @param string $op
     * @param string $col_b
     * @return self
     */
    public function orWhereColumn($col_a, $op, $col_b)
    {
        return $this->whereColumn($col_a, $op, $col_b, SQLConst::SQL_OR);
    }

    /**
     * whereIn
     * 
     * @param string $key
     * @param \Closure|Query|array $value
     * 
     * @return self
     * 
     * ```php
     * $query->whereIn('id', [1, 2, 3, 4]); //WHERE id IN (1, 2, 3, 4)
     * 
     * $query->WhereIn('id', function ($q) { $q->table('table_b')->select('id'])});
     * 
     * $sub_query = DB::table('table_b')->select('id');
     * $query->WhereIn('id', $sub_query);
     * ```
     */
    public function whereIn()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_IN, SQLConst::SQL_AND);
        return $this;
    }

    /**
     * orWhereIn, 用法同whereIn, 链式调用时会使用OR拼接
     * 
     * @param string $key
     * @param \Closure|Query|array $value
     *
     * @return self
     */
    public function orWhereIn()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_IN, SQLConst::SQL_OR);
        return $this;
    }

    /**
     * whereNotIn， 用法同whereIn
     * 
     * @param string $key
     * @param \Closure|Query|array $value
     *
     * @return self
     */
    public function whereNotIn()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_NOT_IN, SQLConst::SQL_AND);
        return $this;
    }

    /**
     * whereNotIn，用法同whereNotIn, 链式调用时会使用OR拼接
     * 
     * @param string $key
     * @param \Closure|Query|array $value
     *
     * @return self
     */
    public function orWhereNotIn()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_NOT_IN, SQLConst::SQL_OR);
        return $this;
    }

    /**
     * whereBetween
     *  
     * @param string $key
     * @param array $between
     * 
     * @return self
     * ```php
     * $query->whereBetween('age', [11, 18]); //WHERE age BETWEEN 11 AND 18
     * ```
     */
    public function whereBetween()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_BETWEEN, SQLConst::SQL_AND);
        return $this;
    }

    /**
     * 用法同whereBetween, 链式调用时会使用OR拼接
     *  
     * @param string $key
     * @param array $between
     * 
     * @return self
     */
    public function orWhereBetween()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_BETWEEN, SQLConst::SQL_OR);
        return $this;
    }

    /**
     * whereNotBetween
     *  
     * @param string $key
     * @param array $between
     * 
     * @return self
     * ```php
     * $query->whereNotBetween('age', [11, 18]); //WHERE age NOT BETWEEN 11 AND 18
     * ```
     */
    public function whereNotBetween()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_NOT_BETWEEN, SQLConst::SQL_AND);
        return $this;
    }

    /**
     * orWhereNotBetween, 用法同whereNotBetween, 链式调用时会使用OR拼接
     *
     * @return void
     */
    public function orWhereNotBetween()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_NOT_BETWEEN, SQLConst::SQL_OR);
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

    /**
     * SQL ON
     *
     * @return self
     */
    public function on()
    {
        $this->abstractOn(func_get_args(), SQLConst::SQL_AND);
        return $this;
    }

    /**
     * orOn, 用法同on, 链式调用时会使用OR拼接
     *
     * @param string $from
     * @param string $op
     * @param string $to
     * @param bool $boolean
     * @return self
     */
    public function orOn()
    {
        $this->abstractOn(func_get_args(), SQLConst::SQL_OR);
        return $this;
    }

    /**
     * SQL OrderBy
     *
     * @param string $col
     * @param string $direction
     * @return self
     */
    public function orderBy($col, $direction = 'ASC')
    {
        $this->orderBy[] = "{$col} {$direction}";
        return $this;
    }

    /**
     * SQL GroupBy
     *
     * @param string $col
     * @return self
     */
    public function groupBy($col)
    {
        $this->groupBy[] = $col;
        return $this;
    }

    /**
     * 重置并重新排序
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
    public function inRandomOrder($column_weight = '')
    {
        $exression = $column_weight 
            ? new Expression("(-LOG(1- RAND())/{$column_weight} + 1)")
            : new Expression('RAND()');
        return $this->reorder($exression, 'ASC');
    }

    /**
     * 悲观锁
     *
     * @return self
     */
    public function lockForUpdate()
    {
        $this->lock = SQLConst::LOCK_FOR_UPDATE;
        return $this;
    }

    /**
     * 乐观锁
     *
     * @return self
     */
    public function sharedLock()
    {
        $this->lock = SQLConst::LOCK_IN_SHARE_MODE;
        return $this;
    }

    /**
     * 偏移offset
     *
     * @param int $offset
     * @return self
     */
    public function offset($offset)
    {
        $offset = (int) $offset;
        if ($offset >= 0) {
            $this->offset = $offset;
        } else {
            throw new InvalidArgumentException("Invalid offset: {$offset}");
        }
        return $this;
    }

    /**
     * same as offset()
     *
     * @param int $offset
     * @return self
     */
    public function skip($offset)
    {
        return $this->offset($offset);
    }

    /**
     * 数据截取limit
     *
     * @param int $limit
     * @return self
     */
    public function limit($limit)
    {
        $limit = (int) $limit;
        if ($limit > 0) {
            $this->limit = $limit;
        } else {
            throw new InvalidArgumentException("Invalid limit: {$limit}");
        }
        return $this;
    }

    /**
     * same as limit()
     *
     * @param int $limit
     * @return self
     */
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
     * 获取一个字段的值
     *
     * @param string $column
     * @return array
     */
    public function pluck($column)
    {
        $this->select = [$column];
        $result = $this->executeSelectQuery();
        return $result ? Util::arrayColumn($result, $column) : [];
    }

    /**
     * 查询一条数据
     *
     * @return null|array
     */
    public function first()
    {
        $this->offset(0)->limit(1);
        $result = $this->executeSelectQuery();
        return $result ? array_pop($result) : null;
    }

    /**
     * 批量查询数据（根据offset递增做分页)
     *
     * @param integer $chunk_size
     * @return \Generator
     */
    public function each($chunk_size = 100)
    {
        $chunk_size = (int) $chunk_size;
        $this->offset($offset = 0)->limit($chunk_size);
        do {
            $count = 0;
            if ($result = $this->executeSelectQuery()) {
                foreach ($result as $row) {
                    $count++;
                    yield $row;
                }
                $this->offset($offset += $chunk_size);
            }
        } while ($count === $chunk_size);
    }

    /**
     * 批量查询数据（根据上一次查询的最大id分页）
     *
     * @param integer $chunk_size
     * @param string $column
     * @return \Generator
     */
    public function eachByColumn($chunk_size = 100, $column)
    {
        $this->limit($chunk_size = (int) $chunk_size)->reorder($column, 'ASC');
        $condition = $this->where;
        $last_id = '';
        do {
            $count = 0;
            $this->where = $condition;
            if ('' !== $last_id) $this->where($column, '>', $last_id);
            if ($result = $this->executeSelectQuery()) {
                foreach ($result as $row) {
                    $count++;
                    yield $row;
                    if (empty($last_id) or ($last_id < $row[$column])) {
                        $last_id = $row[$column];
                    }
                }
            }
        } while ($count === $chunk_size);
    }

    /**
     * 更新数据
     *
     * @param array $data
     * @return int|float rows_affected
     */
    public function update(array $data)
    {
        $this->executeUpdateQuery($data);
        return $this->affectedRows();
    }

    /**
     * 删除数据
     *
     * @return int|float rows_affected
     */
    public function delete()
    {
        $this->executeDeleteQuery();
        return $this->affectedRows();
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
     * 插入并on duplicate update数据
     *
     * @param array $data_insert
     * @param array $data_update
     * @return string $last_insert_id
     */
    public function insertOrUpdate(array $data_insert, array $data_update)
    {
        if (Util::is2DArray($data_update)) {
            throw new BadMethodCallException("Data update must not be 2-d array");
        }
        $this->executeInsertQuery(
            $data_insert, 
            SQLConst::SQL_ON_DUPLCATE_KEY_UPDATE, 
            $data_update
        );
        return $this->lastInsertId();
    }

    /**
     * 直接查询数据
     *
     * @param string $sql
     * @param array $params
     * @return bool|array
     */
    public function execute($sql, array $params)
    {
        return $this->getConnection()->query((string) $sql, $params);
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

    /**
     * 最近插入数据的id（批量插入的话则是第一条数据的id)
     *
     * @return string
     */
    public function lastInsertId()
    {
        return $this->getConnection()->lastInsertId();
    }

    /**
     * 最近一次操作影响的数据行数
     *
     * @return int|float
     */
    public function affectedRows()
    {
        return $this->getConnection()->affectedRows();
    }

    private function selectAggregate($aggregate)
    {
        $this->aggregate = $aggregate;
        $result = $this->executeSelectQuery();
        return $result ? (current(current($result)) + 0) : false;
    }

    /**
     * 获取一个链接，如果没设置的话获取默认链接
     *
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        if ($this->connection) {
            return $this->connection;
        } else {
            return $this->connection = $this->connectionPool->getDefaultConnection();
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
        if (1 === $count_params) {
            $param = $params[0];
            if ($param instanceof Expression) {
                $this->where[] = $param;
            } elseif (is_array($param)) {
                $callable = [$this, 'where'];
                if (Util::isAssoc($param)) {
                    foreach ($param as $k => $v) {
                        call_user_func_array($callable, [$k, $v]);
                    }
                } elseif (Util::is2DArray($param)) {
                    foreach ($param as $row) {
                        call_user_func_array($callable, $row);
                    }
                } else {
                    call_user_func_array($callable, $param);
                }
            } elseif ($param instanceof Closure) {
                $this->whereClosure($op, $param);
            } elseif ($param instanceof Query) {
                $this->whereQuery($op, $param);
            }
        } else {
            $key = $val = null;
            ('' === $op and $count_params === 3) 
                ? (list($key, $op, $val) = $params) 
                : (list($key, $val) = $params);
            if ($val instanceof Closure) {
                $this->whereClosure($op, $val);
            } elseif ($val instanceof Query) {
                $this->whereQuery($op, $val);
            } else {
                $this->where[] = new Where($op, $key, $val);
            }
        }
    }

    /**
     * where-closure
     *
     * @param string $op
     * @param Closure $closure
     * @return void
     */
    private function whereClosure($op, Closure $closure)
    {
        $op_target = [
            SQLConst::SQL_EXISTS, 
            SQLConst::SQL_NOT_EXISTS, 
            SQLConst::SQL_IN, 
            SQLConst::SQL_NOT_IN
        ];
        if (in_array($op, $op_target)) {
            $this->where[] = $op;
            $this->where[] = SQLConst::SQL_LEFTP;
            $closure($query = new self());
            $this->where[] = $query;
            $this->where[] = SQLConst::SQL_RIGHTP;
        } else {
            $this->where[] = SQLConst::SQL_LEFTP;
            $closure($this);
            $this->where[] = SQLConst::SQL_RIGHTP;
        }
    }

    /**
     * where-query
     *
     * @param string $sql
     * @param Query $query
     * @return void
     */
    private function whereQuery($op, Query $query)
    {
        $op_target = [
            SQLConst::SQL_EXISTS, 
            SQLConst::SQL_NOT_EXISTS, 
            SQLConst::SQL_IN,
            SQLConst::SQL_NOT_IN,
        ];
        if (in_array($op, $op_target)) {
            $this->where[] = $op;
            $this->where[] = SQLConst::SQL_LEFTP;
            $this->where[] = $query;
            $this->where[] = SQLConst::SQL_RIGHTP;
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

            case 2:
                list($from, $to) = $params;
                $this->on($from, '=', $to);
                break;

            case 1:
                $closure = current($params);
                $closure($this);
                break;
        }
        $this->join[$joined] = new Join($joined, $join_type, implode(' ', $this->on));
        $this->on = [];
    }

    private function abstractOn(array $params, $boolean = SQLConst::SQL_AND)
    {
        if ($this->on and SQLConst::SQL_LEFTP !== end($this->on)) {
            $this->on[] = $boolean;
        }

        $params = array_slice($params, 0, 3);
        switch ($count_params = count($params)) {
            case 1:
                $param = current($params);
                if ($param instanceof Closure) {
                    $this->on[] = SQLConst::SQL_LEFTP;
                    $param($this);
                    $this->on[] = SQLConst::SQL_RIGHTP;
                }
                break;

            default:
                if (3 === $count_params) {
                    list($key, $op, $val) = $params;
                } else {
                    list($key, $val) = $params;
                    $op = '=';
                }
                $this->on[] = "{$key} {$op} {$val}";
                break;
        }
    }

    /**
     * 执行查询SQL
     *
     * @return void
     */
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

    /**
     * 组装SELECT查询
     *
     * @return array(string|array)
     */
    public function compileSelectQuery()
    {
        $components = [];

        //SELECT
        $components[] = SQLConst::SQL_SELECT;
        if ($this->aggregate) {
            $components[] = $this->aggregate;
        } else {
            $components[] = $this->select ? implode(',', $this->select) : '*';
        }

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
        
        //GROUP BY
        if ($this->groupBy) {
            $components[] = SQLConst::SQL_GROUP_BY;
            $components[] = implode(',', $this->groupBy);
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

        if ($this->lock) {
            $components[] = $this->lock;
        }

        return $this->assemble($components);
    }

    /**
     * 执行update操作
     *
     * @param array $data
     * @return void
     */
    private function executeUpdateQuery($data)
    {
        $this->beforeQuery();
        try {
            list($sql, $params) = $this->compileUpdateQuery($data);
            $this->getConnection()->query($sql, $params);
        } catch (Exception $e) {
            throw $e;
        } finally {
            $this->afterQuery();
        }
    }

    /**
     * 组装UPDATE查询语句
     * 
     * @param $data
     *
     * @return array(string|array)
     */
    public function compileUpdateQuery(array $data)
    {
        $components = [];

        //UPDATE
        $components[] = SQLConst::SQL_UPDATE;
        $components[] = $this->table;

        //JOIN
        if ($this->join) {
            $components = array_merge($components, array_values($this->join));
        }

        //SET
        $components[] = SQLConst::SQL_SET;
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

        return $this->assemble($components);
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
        $this->beforeQuery();
        $components = [];
        $components[] = ($duplicate_handle === SQLConst::SQL_INSERT_IGNORE)
            ? SQLConst::SQL_INSERT_IGNORE
            : SQLConst::SQL_INSERT;
        $components[] = $this->table;
        $components[] = new InsertValue($data_inserted);
        if ($duplicate_handle === SQLConst::SQL_ON_DUPLCATE_KEY_UPDATE) {
            $components[] = SQLConst::SQL_ON_DUPLCATE_KEY_UPDATE;
            $components[] = new SetValue($data_updated);
        }
        
        try {
            list($sql, $params) = $this->assemble($components);
            $this->getConnection()->query($sql, $params);
        } catch (Exception $e) {
            throw $e;
        } finally {
            $this->afterQuery();
        }
    }

    /**
     * 操作DELETE请求
     *
     * @return void
     */
    private function executeDeleteQuery()
    {
        $this->beforeQuery();
        try {
            list($sql, $params) = $this->compileDeleteQuery();
            $this->getConnection()->query($sql, $params);
        } catch (Exception $e) {
            throw $e;
        } finally {
            $this->afterQuery();
        }
    }

    /**
     * 组装Delete语句
     *
     * @return void
     */
    public function compileDeleteQuery()
    {
        $components = [];
        //delete
        $components[] = SQLConst::SQL_DELETE;
        $components[] = SQLConst::SQL_FROM;
        $components[] = $this->table;

        //where
        if ($this->where) {
            $components[] = SQLConst::SQL_WHERE;
            $components = array_merge($components, $this->where);
        }

        //orderBy
        if ($this->orderBy) {
            $components[] = SQLConst::SQL_ORDER_BY;
            $components = array_merge($components, $this->orderBy);
        }

        //limit
        if ($this->limit) {
            $components[] = SQLConst::SQL_LIMIT;
            $components[] = $this->limit;
        }

        return $this->assemble($components);
    }

    private function beforeQuery()
    {
        $this->lastInsertId = '';
        $this->affectedRows = 0;
    }

    private function afterQuery()
    {
        $this->aggregate = '';
        $this->lock = '';
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
            if ($component instanceof Query) {
                list($statement, $values) = $component->compileSelectQuery();
            } elseif ($component instanceof ComponentInterface) {
                $statement = (string) $component;
                $values = $component->values();
            } else {
                $statement = (string) $component;
                $values = null;
            }
            $chunks[] = $statement;
            if ($values) {
                $params = array_merge($params, $values);
            }
        }
        return [Util::implodeWithSpace($chunks), $params];
    }
}
