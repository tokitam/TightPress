<?php

// public/index.php から呼ばれた場合は定数が定義済み、setup.php（CLI）から呼ばれた場合はここで定義する
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR',   __DIR__);
    define('PUBLIC_DIR', __DIR__ . '/public');
}

// Composer オートローダーを読み込む
require_once ROOT_DIR . '/vendor/autoload.php';

use TightPress\Core\Config\DotEnv;
use TightPress\Core\Application;
use TightPress\Core\Config\Config;
use TightPress\Core\Database\Connection;
use TightPress\Core\Database\ConnectionInterface;
use TightPress\Core\Event\EventDispatcher;
use TightPress\Core\Event\ListenerProvider;
use TightPress\Core\Http\Router;
use TightPress\Core\Plugin\PluginManager;
use TightPress\Core\Template\ThemeLoader;
use TightPress\Core\Template\TemplateEngine;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

// .env を config/ ファイルより先に読み込む
DotEnv::load(ROOT_DIR . '/.env');

// DIコンテナ（アプリケーション本体）を生成する
$app = new Application();

// 設定サービスをシングルトンとして登録する
$app->singleton(Config::class, fn() => new Config([
    'app'      => require ROOT_DIR . '/config/app.php',
    'database' => require ROOT_DIR . '/config/database.php',
]));

// DB接続をシングルトンとして登録する
$app->singleton(ConnectionInterface::class, function (Application $app) {
    return Connection::create($app->make(Config::class)->get('database'));
});

// PSR-14 イベントリスナープロバイダーをシングルトンとして登録する
$app->singleton(ListenerProviderInterface::class, fn() => new ListenerProvider());

// PSR-14 イベントディスパッチャーをシングルトンとして登録する
$app->singleton(EventDispatcherInterface::class, function (Application $app) {
    return new EventDispatcher($app->make(ListenerProviderInterface::class));
});

// ルーターをシングルトンとして登録する
$app->singleton(Router::class, fn() => new Router());

// テーマローダーをシングルトンとして登録する
$app->singleton(ThemeLoader::class, function (Application $app) {
    $config = $app->make(Config::class);
    return new ThemeLoader(
        $config->get('app.themes_dir', PUBLIC_DIR . '/themes'),
        $config->get('app.theme', 'twentytwentythree')
    );
});

// テンプレートエンジンをシングルトンとして登録する
$app->singleton(TemplateEngine::class, function (Application $app) {
    return new TemplateEngine($app->make(ThemeLoader::class));
});

// プラグインマネージャーをシングルトンとして登録する
$app->singleton(PluginManager::class, fn() => new PluginManager());

// グローバルヘルパー app() からコンテナを参照できるように登録する
Application::setInstance($app);

// plugins/ ディレクトリ以下のプラグインを読み込んで起動する
$pluginManager = $app->make(PluginManager::class);
$pluginManager->loadFromDirectory(ROOT_DIR . '/plugins');
$pluginManager->registerAll($app);
$pluginManager->bootAll($app);

return $app;
