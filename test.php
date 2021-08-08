<?php

require 'vendor/autoload.php';

use Sue\LegacyModel\Model\Laravel\DB;

// \Sue\LegacyModel\Common\Config::set('driver', 'mysql');
$query = new \Sue\LegacyModel\Model\Laravel\Query();
DB::addConnection('default', [
    'host' => 'localhost',
    'username' => 'root',
    'password' => 'root',
    'port' => 3306,
    'dbname' => 'main'
]);
DB::beginTransaction();



$result = $query->connection('default')
            ->table('user')
            ->where('id', 3)
            ->update(['name' => 'php2']);

DB::commit();
var_dump($result);


DB::addConnection()

var_dump(DB::getQueryLog());