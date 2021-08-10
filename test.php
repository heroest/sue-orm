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
    'dbname' => 'main'
]);

DB::beginTransaction();
// $query = new Query();
// $result = $query->connection('default')
//             ->table('user', 'u')
//             ->where(function ($q) {
//                 $q->where('name', '!=', '');
//                 $q->orWhere('name', '!=', '123');
//             })
//             ->inRandomOrder('age')
//             ->first();
$result = DB::table('user')->insert([
    ['name' => 'zhangxihu', 'age' => 18],
    ['name' => 'zhangdonghu', 'age' => 19]
]);
DB::commit();
var_dump($result);


var_dump(DB::getQueryLog());