<?php

$driver  = $_ENV['DB_DRIVER'] ?? 'sqlite';

// DB_PATH が相対パスの場合はプロジェクトルート基準の絶対パスに変換する
$dbPath = $_ENV['DB_PATH'] ?? './database/tightpress.sqlite';
if (!str_starts_with($dbPath, '/')) {
    $dbPath = dirname(__DIR__) . '/' . ltrim($dbPath, './');
}

return [
    'driver' => $driver,
    'sqlite' => [
        'path' => $dbPath,
    ],
    'mysql'  => [
        'host'     => $_ENV['DB_HOST']    ?? '127.0.0.1',
        'port'     => (int) ($_ENV['DB_PORT'] ?? 3306),
        'database' => $_ENV['DB_NAME']    ?? 'tightpress',
        'username' => $_ENV['DB_USER']    ?? 'root',
        'password' => $_ENV['DB_PASS']    ?? '',
        'charset'  => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
    ],
    'pgsql'  => [
        'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port'     => (int) ($_ENV['DB_PORT'] ?? 5432),
        'database' => $_ENV['DB_NAME'] ?? 'tightpress',
        'username' => $_ENV['DB_USER'] ?? 'postgres',
        'password' => $_ENV['DB_PASS'] ?? '',
    ],
];
