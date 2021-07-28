<?php

require 'vendor/autoload.php';

use Sue\Model\Model\Laravel\DB;

$db = new \Sue\Model\Model\Laravel\Query();

DB::beginTransaction();

$result = $db->table('ea_case_cstm')
            ->where(function ($q) {
                $q->where([['id_c', 'ffdc0c16-d769-5788-9424-5c7756f0f04455']]);
                $q->orWhere('id_c', 'IS NOT', null);
                $q->where(function ($q) {
                    $q->where('1 = 2');
                    $q->orWhere('1 = 3');
                });
            })
            ->where('2 = 4')
            ->first();

DB::rollback();


var_dump(DB::getQueryLog());