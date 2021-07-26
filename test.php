<?php

require 'vendor/autoload.php';


$db = new \Sue\Model\Model\Laravel\Query();
$db->addConnection('default', [
    'host' => 'rm-uf67h6texeyf9f325fo.mysql.rds.aliyuncs.com',
    'dbname' => 'crm_dev',
    'username' => 'crm_dev',
    'password' => 'YKdYXQfhjjzDr281'
]);

$result = $db->table('ea_case_cstm')
            ->where(function ($q) {
                $q->where([['id_c', 'ffdc0c16-d769-5788-9424-5c7756f0f04455']]);
            })
            ->first();

var_dump($result);

var_dump($db->getQueryLog());