#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * TightPress 初回セットアップスクリプト
 *
 * 使い方:
 *   php setup.php          # PHP・拡張チェック + テーブル作成
 *   php setup.php --seed   # 上記 + サンプルデータ投入
 */

$seed = in_array('--seed', $argv ?? [], true);

echo "=== TightPress Setup ===\n\n";

// 1. PHP バージョンチェック
echo "[1/4] PHP バージョン確認...\n";
if (PHP_VERSION_ID < 80100) {
    exit("ERROR: PHP 8.1 以上が必要です（現在: " . PHP_VERSION . "）\n");
}
echo "  OK: PHP " . PHP_VERSION . "\n";

// 2. 必須拡張モジュールチェック
echo "[2/4] 必須拡張モジュール確認...\n";
foreach (['pdo', 'json', 'mbstring'] as $ext) {
    if (!extension_loaded($ext)) {
        exit("ERROR: PHP 拡張 '{$ext}' が必要です。\n");
    }
    echo "  OK: {$ext}\n";
}

// 3. .env チェック
echo "[3/4] 設定ファイル確認...\n";
if (!file_exists(__DIR__ . '/.env')) {
    if (file_exists(__DIR__ . '/.env.example')) {
        copy(__DIR__ . '/.env.example', __DIR__ . '/.env');
        echo "  INFO: .env.example を .env にコピーしました。内容を確認・編集してから再実行してください。\n";
        exit(0);
    }
    exit("ERROR: .env ファイルが見つかりません。\n");
}
echo "  OK: .env\n";

// database/ ディレクトリが存在しない場合は作成する
if (!is_dir(__DIR__ . '/database')) {
    mkdir(__DIR__ . '/database', 0755, true);
}

// 4. DB 接続 + マイグレーション（プラグインの install() を実行）
echo "[4/4] データベースのセットアップ...\n";

$app = require __DIR__ . '/bootstrap.php';

use TightPress\Core\Plugin\PluginManager;
use TightPress\Database\Seeds\PostSeeder;
use TightPress\Database\Seeds\PageSeeder;

$pluginManager = $app->make(PluginManager::class);
foreach ($pluginManager->getActive() as $id => $plugin) {
    $plugin->install($app);
    echo "  OK: {$id} テーブル作成\n";
}

// オプション: シードデータ投入
if ($seed) {
    echo "\nシードデータを投入中...\n";
    (new PostSeeder($app))->run();
    (new PageSeeder($app))->run();
    echo "  OK: シードデータ投入完了\n";
}

echo "\nセットアップ完了！\n";
echo "起動: php -S localhost:8080 -t public public/index.php\n";
