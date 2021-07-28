<?php

require 'vendor/autoload.php';

use Sue\Model\Model\Laravel\DB;

// \Sue\Model\Common\Config::set('driver', 'mysql');
$query = new \Sue\Model\Model\Laravel\Query();
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


var_dump(DB::getQueryLog());