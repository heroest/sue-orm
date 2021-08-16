<?php

namespace Sue\LegacyModel\Model\Component;

use Sue\LegacyModel\Common\SQLConst;
use Sue\LegacyModel\Common\Util;
use Sue\LegacyModel\Model\Contracts\ComponentInterface;
use Sue\LegacyModel\Model\Component\Expression;

class Where implements ComponentInterface
{
    private $values = [];
    private $statement = '';
    
    /**
     * 构造where条件
     *
     * @param string|null $op
     * @param string $key
     * @param string|array|Expression|null $val
     */
    public function __construct($op, $key, $val)
    {
        $op = strtoupper($op);
        $ph = Util::ph();

        switch (true) {
            case null === $val:
                $op = $op = $op ?: 'IS';
                $this->statement = "{$key} {$op} NULL";
                break;

            case $val instanceof Expression:
                $this->statement = "{$key} {$op} {$val}";
                break;

            case is_array($val): 
                if (in_array($op, [SQLConst::SQL_IN, SQLConst::SQL_NOT_IN])) {
                    $this->values = $val;
                    $ph = implode(',', array_fill(0, count($val), $ph));
                    $this->statement = "{$key} {$op} ({$ph})";
                } elseif (in_array($op, [SQLConst::SQL_BETWEEN, SQLConst::SQL_NOT_BETWEEN])) {
                    $this->values = $val;
                    $this->statement = "{$key} {$op} {$ph} AND {$ph}";
                }
                break;

            default:
                $this->values[] = $val;
                $op = $op ?: '=';
                $this->statement = "{$key} {$op} {$ph}";
                break;
        }
    }

    public function values()
    {
        return $this->values;
    }

    public function __toString()
    {
        return $this->statement;
    }

    public static function normalizeParams(array $params, $op)
    {
        switch ($op) {
            case SQLConst::SQL_IN:
            case SQLConst::SQL_NOT_IN:
            case SQLConst::SQL_BETWEEN:
            case SQLConst::SQL_NOT_BETWEEN:
                $max_len = 2;
                break;

            default:
                $max_len = 3;
                break;
        }
        return array_slice($params, 0, $max_len);
    }
}
