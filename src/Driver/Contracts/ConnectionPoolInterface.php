<?php

namespace Sue\Model\Driver\Contracts;

interface ConnectionPoolInterface
{
    public function connection($name);

    public function addConnection($connection, $mixed);
}