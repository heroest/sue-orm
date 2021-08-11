<?php

namespace Sue\LegacyModel\Common;

use Sue\LegacyModel\Model\Component\Expression;
use Sue\LegacyModel\Common\SQLConst;

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
        $ph = self::ph();
        $ph_len = strlen($ph);
        foreach ($params as $param) {
            if (null === $param) {
                $param = 'null';
            } elseif (is_int($param)) {
                $param = (int) $param;
            } elseif (is_float($param)) {
                $param = (float) $param;
            } elseif (!($param instanceof Expression)) {
                $param = (string) $param;
                $param = "'{$param}'";
            }

            $index = stripos($line, $ph, 0);
            $head = substr($line, 0, $index);
            $tail = substr($line, $index + $ph_len);
            $line = "{$head}{$param}{$tail}";
        }
        return $line;
    }

    public static function implodeWithSpace(array $items)
    {
        $line = '';
        static $map = [];
        static $tree = [
            [SQLConst::SQL_LEFTP, '*'],
            ['*', SQLConst::SQL_RIGHTP],
            ['*', SQLConst::SQL_COMMA],
            [SQLConst::SQL_RIGHTP, SQLConst::SQL_COMMA],
            [SQLConst::SQL_COMMA, SQLConst::SQL_LEFTP],
            [SQLConst::SQL_COMMA, '*'],
        ];
        if (empty($map)) {
            foreach ($tree as $branch) {
                $map[$branch[0]] = true;
                $map[$branch[1]] = true;
            }
        }

        reset($items);
        while ($current = current($items)) {
            $line .= $current;
            if (false === $next = next($items)) {
                break;
            }
            $h = isset($map[$current]) ? $current : '*';
            $t = isset($map[$next]) ? $next : '*';
            $append_space = true;
            foreach ($tree as $rule) {
                if ($h === $rule[0] and $t === $rule[1]) {
                    $append_space = false;
                    break;
                }
            }
            if ($append_space) {
                $line .= ' ';
            }
        }
        return $line;
    }

    public static function is2DArray(array $values)
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

    public static function ph()
    {
        return Config::get('driver') === 'mysql' ? '\_<???>_/' : '?';
    }
}
