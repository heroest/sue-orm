<?php

namespace Sue\LegacyModel\Common;

abstract class SQLConst
{
    const SQL_AND = 'AND';
    const SQL_OR = 'OR';

    const SQL_LEFTP = '(';
    const SQL_RIGHTP = ')';
    const SQL_COMMA = ',';

    const SQL_WHERE = 'WHERE';
    const SQL_IN = 'IN';
    const SQL_NOT_IN = 'NOT IN';
    const SQL_BETWEEN = 'BETWEEN';
    const SQL_NOT_BETWEEN = 'NOT BETWEEN';
    const SQL_LIKE = 'LIKE';
    const SQL_NOT_LIKE = 'NOT LIKE';
    const SQL_EXISTS = 'EXISTS';
    const SQL_NOT_EXISTS = 'NOT EXISTS';

    const SQL_SELECT = 'SELECT';
    const SQL_FROM = 'FROM';
    const SQL_LIMIT = 'LIMIT';
    const SQL_ORDER_BY = 'ORDER BY';
    const SQL_GROUP_BY = 'GROUP BY';

    const SQL_UPDATE = 'UPDATE';
    const SQL_SET = 'SET';

    const SQL_DELETE = 'DELETE';

    const SQL_INSERT = 'INSERT INTO';
    const SQL_INSERT_IGNORE = 'INSERT IGNORE INTO';
    const SQL_ON_DUPLCATE_KEY_UPDATE = 'ON DUPLICATE KEY UPDATE';
    const SQL_VALUES = 'VALUES';

    const SQL_INNER_JOIN = 'INNER JOIN';
    const SQL_LEFT_JOIN = 'LEFT JOIN';
    const SQL_RIGHT_JOIN = 'RIGHT JOIN';

    const LOCK_FOR_UPDATE = 'FOR UPDATE';
    const LOCK_IN_SHARE_MODE = 'LOCK IN SHARE MODE';
}