<?php

declare(strict_types=1);

/**
 * シードデータ投入エントリポイント
 *
 * Composer オートローダーとアプリケーションブートストラップを読み込み、
 * PostSeeder と PageSeeder を順に実行してサンプルデータを投入する。
 *
 * 使い方:
 *   php database/seeds/seed.php
 */

// Composer オートローダーを読み込む
require __DIR__ . '/../../vendor/autoload.php';

// アプリケーションをブートストラップして DI コンテナを取得する
$app = require __DIR__ . '/../../bootstrap.php';

use TightPress\Database\Seeds\PostSeeder;
use TightPress\Database\Seeds\PageSeeder;

// 記事サンプルデータを投入する
echo "Seeding posts...\n";
(new PostSeeder($app))->run();

// 固定ページサンプルデータを投入する
echo "Seeding pages...\n";
(new PageSeeder($app))->run();

echo "Done.\n";
