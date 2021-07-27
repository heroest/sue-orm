<?php

namespace Sue\Model\Common;

use Sue\Model\Model\Component\Expression;

class Util
{
    /**
     * 编译一条SQL执行
     *
     * @param string $sql
     * @param array $params
     * @return void
     */
    public static function compileSQL($sql, array $params = [])
    {
        if (empty($params)) {
            return $sql;
        }

        $line = $sql;
        foreach ($params as $param) {
            if (null === $param) {
                $param = 'null';
            } elseif (is_int($param)) {
                $param = (int) $param;
            } elseif (is_float($param)) {
                $param = (float) $param;
            } elseif ($param instanceof Expression) {
                $param = $param;
            } else {
                $param = (string) $param;
                $param = "'{$param}'";
            }

            $index = stripos($line, '?', 0);
            $head = substr($line, 0, $index);
            $tail = substr($line, $index + 1);
            $line = "{$head}{$param}{$tail}";
        }
        return $line;
    }
}