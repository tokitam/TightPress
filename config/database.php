<?php

return [
    'driver' => 'sqlite',   // 'sqlite' | 'mysql' | 'pgsql'
    'sqlite' => [
        'path' => __DIR__ . '/../database/tightpress.sqlite',
    ],
    'mysql'  => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'tightpress',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    'pgsql'  => [
        'host'     => '127.0.0.1',
        'port'     => 5432,
        'database' => 'tightpress',
        'username' => 'postgres',
        'password' => '',
    ],
];
