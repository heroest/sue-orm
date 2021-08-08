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
use Sue\LegacyModel\Driver\ConnectionPool;
use Sue\LegacyModel\Driver\Contracts\ConnectionInterface;
use Sue\LegacyModel\Model\Contracts\ComponentInterface;
use Sue\LegacyModel\Model\Component\Where;
use Sue\LegacyModel\model\Component\Join;
use Sue\LegacyModel\Model\Component\Expression;
use Sue\LegacyModel\Model\Component\SetValue;

/**
 * 数据库查询构造器
 */
class Query
{
    /** @var ConnectionPool $connectionPool */
    private $connectionPool = null;
    /** @var ConnectionInterface $connection */
    private $connection;

    private $model;
    private $table;

    /** @var string $lastInsertId */
    private $lastInsertId = '';
    /** @var int|float $affectedRows */
    private $affectedRows = 0;

    private $select = [];
    private $where = [];
    private $join = [];
    private $limit = 0;
    private $offset = 0;

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

    public function from($table, $as = null)
    {
        $this->table = $as ? "{$table} AS {$as}" : $table;
        return $this;
    }

    /**
     * from
     *
     * @return self
     */
    public function table()
    {
        return call_user_func_array([$this, 'from'], func_get_args());
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

    public function whereLike()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_LIKE, SQLConst::SQL_AND);
        return $this;
    }

    public function orWhereLike()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_LIKE, SQLConst::SQL_OR);
        return $this;
    }

    public function whereNotLike()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_NOT_LIKE, SQLConst::SQL_AND);
        return $this;
    }

    public function orWhereNotLike()
    {
        $this->abstractWhere(func_get_args(), SQLConst::SQL_NOT_LIKE, SQLConst::SQL_OR);
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
     * @return int $last_inserted_id
     */
    public function insert(array $data)
    {
        return $this->executeInsertQuery($data);
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

    private function abstractJoin(array $params, $op = '')
    {
        $this->on = [];
        $params = Join::normalizeParams($params, $op);
        $joined = array_shift($params);
        
        switch (count($params)) {
            case 3:
                list($match_from, $op, $match_to) = $params;
                $this->on($match_from, $op, $match_to);
                break;

            case 1:
                $closure = current($params);
                $closure($this);
                break;
        }
        $this->join[$joined] = new Join([$joined, $op, implode(' ', $this->on)]);
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

        //LIMITE
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

        //LIMITE
        if ($this->limit) {
            $components[] = SQLConst::SQL_LIMIT;
            $components[] = "{$this->offset},{$this->limit}";
        }

        list($sql, $params) = $this->assemble($components);
        $this->getConnection()->query($sql, $params);
        return $this->affectedRows = $this->getConnection()->affectedRows();
    }

    private function executeInsertQuery(array $data, $duplicate_handle = '')
    {

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
            $chunks[] = trim(strval($component));
            if ($component instanceof ComponentInterface) {
                $params = array_merge($params, $component->values());
            }
        }
        return [implode(' ', $chunks), $params];
    }
}
