<?php

require 'vendor/autoload.php';

use Sue\LegacyModel\Model\Laravel\DB;
use Sue\LegacyModel\Model\Laravel\Query;

DB::setDrive('mysqli');
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
// $result = DB::table('user')->insertOrUpdate([
//     ['name' => 'zhangxihu2', 'age' => 18],
//     ['name' => 'zhangdonghu2', 'age' => 19]
// ], ['age' => DB::raw('age + 1')]);

$query = DB::table('user')->where('id', '>', 24);
foreach ($query->eachByColumn(2, 'id') as $row) {
    print_r($row);
}
DB::commit();
var_dump(DB::getQueryLog());