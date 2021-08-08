<?php

namespace Sue\LegacyModel\Model\Component;

use Sue\LegacyModel\Common\SQLConst;
use Sue\LegacyModel\Model\Contracts\ComponentInterface;

class Join implements ComponentInterface
{
    private $values = [];
    private $statement = '';

    public function __construct(array $params)
    {

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