<?php

namespace Sue\LegacyModel\Model\Contracts;

interface ComponentInterface
{
    /**
     * 返回参数
     *
     * @return array
     */
    public function values();

    public function __toString();
}