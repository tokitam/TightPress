<?php

namespace TightPress\Plugin\Pages;

use TightPress\Core\Application;
use TightPress\Core\Plugin\PluginInterface;
use TightPress\Core\Plugin\PluginMeta;
use TightPress\Core\Http\Router;
use TightPress\Core\Database\ConnectionInterface;
use TightPress\Plugin\Pages\Http\PageController;
use TightPress\Plugin\Pages\Model\PageRepository;
use TightPress\Plugin\Pages\Migration\CreatePagesTables;

/**
 * pages プラグインのメインクラス。
 * 固定ページの管理・表示に関するサービス登録・ルート登録・マイグレーションを担う。
 *
 * /{slug} のルートを提供し、blog-posts プラグインの /posts/{slug} より低い優先度で登録されるため、
 * ブログ記事ルートと競合しない。
 */
class PagesPlugin implements PluginInterface
{
    /**
     * プラグインのメタ情報を返す。
     *
     * @return PluginMeta プラグインID・名前・バージョン等を含むメタ情報
     */
    public function meta(): PluginMeta
    {
        return new PluginMeta(
            id: 'pages',
            name: 'Pages',
            version: '1.0.0',
            description: '固定ページの管理・表示機能を提供するプラグイン',
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
        // PageRepository をシングルトンとして登録する
        // ConnectionInterface はコアが既にバインド済みのため make() で取得する
        $app->singleton(PageRepository::class, function (Application $app) {
            return new PageRepository(
                $app->make(ConnectionInterface::class)
            );
        });
    }

    /**
     * プラグインを起動する。
     * ルーターに固定ページ詳細のルートを登録する。
     *
     * blog-posts プラグインより後に登録されるため優先度は低く、
     * /posts/{slug} などのブログ記事ルートが先にマッチする。
     *
     * @param Application $app DIコンテナ
     */
    public function boot(Application $app): void
    {
        // ルーターを取得する
        $router = $app->make(Router::class);

        // 固定ページ詳細ルートを登録する（blog-posts より後に登録するため低優先度）
        $router->get('/{slug}', function ($request, $params) use ($app) {
            $controller = $app->make(PageController::class);
            return $controller->show($request, $params);
        });
    }

    /**
     * プラグインのインストール処理。
     * pages / page_meta テーブルを作成する。
     *
     * @param Application $app DIコンテナ
     */
    public function install(Application $app): void
    {
        // マイグレーションクラスを生成してテーブルを作成する
        $migration = new CreatePagesTables(
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
        $migration = new CreatePagesTables(
            $app->make(ConnectionInterface::class)
        );
        $migration->down();
    }
}
