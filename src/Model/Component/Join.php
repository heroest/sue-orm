<?php

namespace Sue\LegacyModel\Model\Component;

use Sue\LegacyModel\Common\SQLConst;
use Sue\LegacyModel\Model\Contracts\ComponentInterface;

class Join implements ComponentInterface
{
    private $statement = '';

    public function __construct($joined, $join_type, $on)
    {
        $this->statement = "{$join_type} {$joined} ON {$on}";
    }

    public function values()
    {
        return [];
    }

    public function __toString()
    {
        return $this->statement;
    }

    public static function normalizeParams(array $params, $join_type)
    {
        $max_len = 4;
        return array_slice($params, 0, $max_len);
    }
}