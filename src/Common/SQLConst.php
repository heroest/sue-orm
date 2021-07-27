<?php

namespace Sue\Model\Common;

abstract class SQLConst
{
    const SQL_AND = 'AND';
    const SQL_OR = 'OR';

    const SQL_LEFTP = '(';
    const SQL_RIGHTP = ')';

    const SQL_WHERE = 'WHERE';
    const SQL_IN = 'IN';
    const SQL_NOT_IN = 'NOT IN';
    const SQL_BETWEEN = 'BETWEEN';
    const SQL_NOT_BETWEEN = 'NOT BETWEEN';
    const SQL_LIKE = 'LIKE';
    const SQL_NOT_LIKE = 'NOT LIKE';

    const SQL_SELECT = 'SELECT';
    const SQL_FROM = 'FROM';
    const SQL_LIMIT = 'LIMIT';
    const SQL_UPDATE = 'UPDATE';
    const SQL_SET = 'SET';
}