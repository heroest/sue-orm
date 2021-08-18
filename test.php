<?php

require 'vendor/autoload.php';

use Sue\LegacyModel\Model\Laravel\DB;
use Sue\LegacyModel\Model\Laravel\Query;

DB::setDrive('mysql');
DB::addConnection('default', [
    'host' => 'localhost',
    'username' => 'root',
    'password' => 'root',
    'port' => 3306,
    'dbname' => 'main',
    'charset' => 'utf8mb4'
]);

set_error_handler(function ($error_no, $error_str, $error_file, $error_line) {
    throw new ErrorException($error_str, $error_no, E_USER_ERROR, $error_file, $error_line);
});

DB::beginTransaction();
$query = new Query();
$result = $query->table('user', 'u')
            ->where(function ($q) {
                $q->where('name', '!=', '');
                $q->orWhere('name', '!=', '123');
            })
            ->inRandomOrder('age')
            ->first();
$result = DB::table('user')->insertOrUpdate([
    ['name' => 'zhangxihu2', 'age' => 18],
    ['name' => 'zhangdonghu2', 'age' => 19]
], ['age' => DB::raw('age + 1')]);
try {
    $result = DB::table('user')->where('id', null)->whereExist(function ($q) {
        $q->from('user', 'ub')->where('ub.id', '=', 'user.id');
    })->lockForUpdate()->get();
} catch (Exception $e) {
    echo $e;
}

var_dump($result);
DB::commit();
var_dump(DB::getQueryLog());