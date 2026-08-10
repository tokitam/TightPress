<?php

// プロジェクトルートと公開ディレクトリの絶対パスを定義する
define('ROOT_DIR',   dirname(__DIR__));
define('PUBLIC_DIR', __DIR__);

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
