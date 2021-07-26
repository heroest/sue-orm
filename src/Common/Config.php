<?php

namespace Sue\Model\Common;

use Sue\Model\Common\AbstractSingleton;

class Config extends AbstractSingleton
{
    private $storage = [];

    public function get($key, $default_value = null)
    {
        return isset($this->storage[$key])
            ? $this->storage[$key]
            : $default_value;
    }

    public function set($key, $val)
    {
        $this->storage[$key] = $val;
    }

    /**
     * 当key不存在的时候设置key,val
     *
     * @param string $key
     * @param mixed $val
     * @return mixed
     */
    public function setnx($key, $val)
    {
        if (array_key_exists($key, $this->storage)) {
            return false;
        } else {
            $this->storage[$key] = $val;
            return true;
        }
    }
}
