<?php

namespace Sue\LegacyModel\Model\Component;

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
}