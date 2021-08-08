<?php

namespace Sue\LegacyModel\Model\Component;

use Sue\LegacyModel\Model\Contracts\ComponentInterface;

class Expression implements ComponentInterface
{
    private $statement;

    /**
     * SQL表达式
     *
     * @param string $statement
     */
    public function __construct($statement)
    {
        $this->statement = (string) $statement;
    }

    public function __toString()
    {
        return $this->statement;
    }

    public function values()
    {
        return [];
    }
}