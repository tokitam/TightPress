# プラグインシステム設計

## 設計方針

- ブログ記事管理・固定ページ管理は「コア機能」ではなくプラグインとして実装する
- プラグインはコアの DI コンテナ・イベントシステム・ルーターを通じてのみコアと連携する
- プラグイン同士は直接依存せず、イベント経由で連携する
- Phase 1 では blog-posts / pages プラグインをバンドルして提供する

---

## PluginInterface

全プラグインはこのインターフェースを実装する。

```php
namespace TightPress\Core\Plugin;

interface PluginInterface
{
    /**
     * プラグインのメタ情報を返す
     */
    public function meta(): PluginMeta;

    /**
     * プラグインが依存するサービスを DI コンテナに登録する
     * ※ boot() より前に呼ばれる
     *
     * @param Application $app
     */
    public function register(Application $app): void;

    /**
     * プラグインを起動する
     * ルート・イベントリスナー・管理画面メニューなどを登録する
     *
     * @param Application $app
     */
    public function boot(Application $app): void;

    /**
     * プラグインのインストール処理（テーブル作成など）
     */
    public function install(Application $app): void;

    /**
     * プラグインのアンインストール処理（テーブル削除など）
     */
    public function uninstall(Application $app): void;
}
```

---

## PluginMeta

```php
namespace TightPress\Core\Plugin;

final class PluginMeta
{
    public function __construct(
        public readonly string  $id,          // 'blog-posts'
        public readonly string  $name,        // 'Blog Posts'
        public readonly string  $version,     // '1.0.0'
        public readonly string  $description,
        public readonly string  $author,
        public readonly string  $entryFile,   // plugin.php の絶対パス
    ) {}
}
```

---

## PluginManager

```php
namespace TightPress\Core\Plugin;

class PluginManager
{
    /** @var array<string, PluginInterface> */
    private array $plugins = [];

    /**
     * プラグインを登録する
     */
    public function register(PluginInterface $plugin): void;

    /**
     * 全プラグインの register() を呼び出す（DI バインディングの登録）
     */
    public function registerAll(Application $app): void;

    /**
     * 全プラグインの boot() を呼び出す（ルート・リスナーの登録）
     */
    public function bootAll(Application $app): void;

    /**
     * 指定プラグインをインストールする
     *
     * @param string $pluginId
     */
    public function install(string $pluginId, Application $app): void;

    /**
     * 指定プラグインをアンインストールする
     */
    public function uninstall(string $pluginId, Application $app): void;

    /**
     * 有効なプラグイン一覧を返す
     *
     * @return array<string, PluginInterface>
     */
    public function getActive(): array;
}
```

---

## プラグインのディレクトリ規約

```
plugins/
└── {plugin-id}/
    ├── plugin.php          # エントリポイント（PluginInterface を implements したクラスを返す）
    ├── composer.json       # プラグイン独自の依存（任意）
    └── src/
        └── ...
```

`plugin.php` は以下の形式でクラスインスタンスを返す。

```php
// plugins/blog-posts/plugin.php
<?php

use TightPress\Plugin\BlogPosts\BlogPostPlugin;

return new BlogPostPlugin();
```

`PluginManager` はこのファイルを `require` して戻り値を受け取る。

---

## バンドルプラグイン（Phase 1）

### blog-posts プラグイン

| 機能 | 詳細 |
|---|---|
| テーブル管理 | `posts` / `post_meta` / `post_term_relationships` / `comments` |
| ルート | `GET /` (一覧) / `GET /posts/{slug}` (詳細) |
| テンプレート | `home.php` / `single.php` / `single-post.php` |
| イベント発行 | `PostPublished` / `PostSaved` / `PostDeleted` |
| イベント購読 | なし（Phase 1 では最小限） |

### pages プラグイン

| 機能 | 詳細 |
|---|---|
| テーブル管理 | `pages` / `page_meta` |
| ルート | `GET /{slug}` (固定ページ表示) |
| テンプレート | `page.php` / `page-{slug}.php` |
| イベント発行 | `PagePublished` / `PageSaved` / `PageDeleted` |

---

## プラグイン有効化の管理

有効なプラグインの一覧は `options` テーブルに JSON で保存する。

```
options.name = 'active_plugins'
options.value = '["blog-posts","pages"]'
```

Phase 5 でプラグインのインストール UI を実装する際に、
この値を読み書きして有効/無効を切り替える。

---

## Phase 5: プラグインインストールの流れ

1. 管理画面からプラグイン ZIP をアップロード
2. `plugins/{plugin-id}/` に展開
3. `PluginManager::install()` を呼び出す
   - `PluginInterface::install()` が実行される（テーブル作成など）
4. `active_plugins` オプションにプラグイン ID を追加
5. 次回リクエストからプラグインが有効になる

アンインストール:

1. 管理画面からアンインストール操作
2. `PluginManager::uninstall()` を呼び出す
   - `PluginInterface::uninstall()` が実行される（テーブル削除など）
3. `active_plugins` オプションからプラグイン ID を除去
4. プラグインディレクトリを削除（任意）
