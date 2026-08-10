<?php

namespace TightPress\Plugin\BlogPosts;

use TightPress\Core\Application;
use TightPress\Core\Plugin\PluginInterface;
use TightPress\Core\Plugin\PluginMeta;
use TightPress\Core\Http\Router;
use TightPress\Core\Database\ConnectionInterface;
use TightPress\Core\Template\TemplateEngine;
use TightPress\Core\Template\ThemeLoader;
use TightPress\Plugin\BlogPosts\Http\PostController;
use TightPress\Plugin\BlogPosts\Model\PostRepository;
use TightPress\Plugin\BlogPosts\Migration\CreatePostsTables;

/**
 * blog-posts プラグインのメインクラス。
 * ブログ記事の管理・表示に関するサービス登録・ルート登録・マイグレーションを担う。
 */
class BlogPostPlugin implements PluginInterface
{
    /**
     * プラグインのメタ情報を返す。
     *
     * @return PluginMeta プラグインID・名前・バージョン等を含むメタ情報
     */
    public function meta(): PluginMeta
    {
        return new PluginMeta(
            id: 'blog-posts',
            name: 'Blog Posts',
            version: '1.0.0',
            description: 'ブログ記事の管理・表示機能を提供するプラグイン',
            author: 'TightPress',
            entryFile: __FILE__,
        );
    }

    /**
     * プラグインが依存するサービスを DI コンテナに登録する。
     * boot() より前に呼ばれる。
     *
     * @param Application $app DIコンテナ
     */
    public function register(Application $app): void
    {
        // PostRepository をシングルトンとして登録する
        $app->singleton(PostRepository::class, function (Application $app) {
            return new PostRepository($app->make(ConnectionInterface::class));
        });

        // PostController をシングルトンとして登録する
        $app->singleton(PostController::class, function (Application $app) {
            return new PostController(
                $app->make(PostRepository::class),
                $app->make(TemplateEngine::class),
                $app->make(ThemeLoader::class),
                $app,
            );
        });
    }

    /**
     * プラグインを起動する。
     * ルーターに記事一覧・記事詳細のルートを登録する。
     *
     * @param Application $app DIコンテナ
     */
    public function boot(Application $app): void
    {
        // ルーターを取得する
        $router = $app->make(Router::class);

        // 記事一覧ルート（トップページ）を登録する
        $router->get('/', function ($request, $params) use ($app) {
            $controller = $app->make(PostController::class);
            return $controller->index($request, $params);
        });

        // 記事詳細ルートを登録する
        $router->get('/posts/{slug}', function ($request, $params) use ($app) {
            $controller = $app->make(PostController::class);
            return $controller->show($request, $params);
        });
    }

    /**
     * プラグインのインストール処理。
     * posts / post_meta / post_term_relationships / comments テーブルを作成する。
     *
     * @param Application $app DIコンテナ
     */
    public function install(Application $app): void
    {
        // マイグレーションクラスを生成してテーブルを作成する
        $migration = new CreatePostsTables(
            $app->make(ConnectionInterface::class)
        );
        $migration->up();
    }

    /**
     * プラグインのアンインストール処理。
     * install() で作成したテーブルを全て削除する。
     *
     * @param Application $app DIコンテナ
     */
    public function uninstall(Application $app): void
    {
        // マイグレーションクラスを生成してテーブルを削除する
        $migration = new CreatePostsTables(
            $app->make(ConnectionInterface::class)
        );
        $migration->down();
    }
}
