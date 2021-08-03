<?php

namespace Sue\LegacyModel\Model\Component;

use Sue\LegacyModel\Model\Contracts\ComponentInterface;
use Sue\LegacyModel\Model\Component\Expression;
use Sue\LegacyModel\Common\SQLConst;

class SetValue implements ComponentInterface
{
    private $statement = '';
    private $values = [];

    public function __construct(array $data)
    {
        $items = [];
        foreach ($data as $k => $v) {
            if ($v instanceof Expression) {
                $items[] = "{$k} = {$v}";
            } else {
                $items[] = "{$k} = ?";
                $this->values[] = $v;
            }
        }
        $this->statement = SQLConst::SQL_SET . ' ' . implode(', ', $items);
    }

    public function __toString()
    {
        return $this->statement;
    }

    public function values()
    {
        return $this->values;
    }
}