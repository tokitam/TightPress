<?php

// プロジェクトルートと公開ディレクトリの絶対パスを定義する
define('ROOT_DIR',   dirname(__DIR__));
define('PUBLIC_DIR', __DIR__);

// PHP 組み込みサーバー用: 実在する静的ファイルはそのまま配信する
if (PHP_SAPI === 'cli-server') {
    $staticFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($staticFile)) {
        return false;
    }
}

$app = require ROOT_DIR . '/bootstrap.php';

use TightPress\Core\Http\Request;
use TightPress\Core\Http\Router;

// グローバル変数（$_GET, $_POST 等）からリクエストオブジェクトを生成する
$request = Request::fromGlobals();

// ルーターでリクエストを処理してレスポンスを取得する
$router   = $app->make(Router::class);
$response = $router->dispatch($request);

// レスポンスをHTTPヘッダー＋ボディとして出力する
$response->send();
