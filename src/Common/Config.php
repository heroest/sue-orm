<?php

namespace Sue\LegacyModel\Common;

class Config
{
    private static $storage = [];

    public static function get($key, $default_value = null)
    {
        return isset(self::$storage[$key])
            ? self::$storage[$key]
            : $default_value;
    }

    public static function set($key, $val)
    {
        self::$storage[$key] = $val;
    }

    public static function destroy()
    {
        self::$storage = [];
    }

    /**
     * 当key不存在的时候设置key
     *
     * @param string $key
     * @param mixed $val
     * @return mixed
     */
    public static function setnx($key, $val)
    {
        if (array_key_exists($key, self::$storage)) {
            return false;
        } else {
            self::$storage[$key] = $val;
            return true;
        }
    }
}
