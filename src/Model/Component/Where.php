<?php

namespace Sue\LegacyModel\Model\Component;

use Sue\LegacyModel\Common\SQLConst;
use Sue\LegacyModel\Model\Contracts\ComponentInterface;

class Where implements ComponentInterface
{
    private $values = [];
    private $statement = '';

    public function __construct(array $params)
    {
        $op = strtoupper(array_shift($params));
        $key = array_shift($params);

        switch ($op) {
            case 'IN':
            case 'NOT IN':
                $this->values = $params;
                $ph = implode(',', array_fill(0, count($params), '?'));
                $this->statement = "{$key} {$op} ({$ph})";
                break;

            case 'BETWEEN':
            case 'NOT BETWEEN':
                $this->values = $params;
                $this->statement = "{$key} {$op} ? AND ?";
                break;

            default:
                $this->values[] = array_shift($params);
                $this->statement = "{$key} {$op} ?";
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
        $max_len = count($params);
        switch ($op) {
            case '':
                $max_len = 3;
                break;

            case SQLConst::SQL_IN:
            case SQLConst::SQL_NOT_IN:
            case SQLConst::SQL_BETWEEN:
            case SQLConst::SQL_NOT_BETWEEN:
                $max_len = 2;
                break;
        }
        return array_slice($params, 0, $max_len);
    }
}
