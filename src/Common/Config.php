<?php

namespace Sue\Model\Common;

use Sue\Model\Common\AbstractSingleton;

class Config extends AbstractSingleton
{
    private $storage = [
        'driver' => 'default'
    ];

    public function get($key, $default_value = null)
    {
        return isset($this->storage[$key])
            ? $this->storage[$key]
            : $default_value;
    }

    public function set($key, $val)
    {
    }
}
