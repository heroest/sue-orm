<?php

namespace Sue\LegacyModel\Model\Component;

use Sue\LegacyModel\Common\SQLConst;
use Sue\LegacyModel\Common\Util;
use Sue\LegacyModel\Model\Contracts\ComponentInterface;

class InsertValue implements ComponentInterface
{
    private $statement = '';
    private $values = [];

    public function __construct(array $data)
    {
        $components = [];
        $components[] = SQLConst::SQL_LEFTP;
        if(!self::isTwoDimensional($data)) {
            $data = [$data];
        }
        $components[] = implode(',', array_keys($data[0]));
        $components[] = SQLConst::SQL_RIGHTP;
        $components[] = SQLConst::SQL_VALUES;
        foreach ($data as $row) {
            if (end($components) !== SQLConst::SQL_VALUES) {
                $components[] = SQLConst::SQL_COMMA;
            }
            $components[] = SQLConst::SQL_LEFTP;
            $params = array_values($row);
            $components[] = implode(',', array_fill(0, count($params), Util::ph()));
            $this->values = array_merge($this->values, $params);
            $components[] = SQLConst::SQL_RIGHTP;
        }
        $this->statement = Util::implodeWithSpace($components);
    }

    public function values()
    {
        return $this->values;
    }

    public function __toString()
    {
        return $this->statement;
    }

    private static function isTwoDimensional(array $values)
    {
        $pre = -1;
        foreach ($values as $i => $row) {
            if (1 !== ($i - $pre++)) {
                return false;
            } elseif (!is_array($row)) {
                return false;
            }
        }
        return true;
    }
}