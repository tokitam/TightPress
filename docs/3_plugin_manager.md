# [Phase 1] 1-7. プラグインマネージャー

## 概要

プラグインの登録・起動・インストール・アンインストールを管理するプラグインシステムを実装した。
コアの DI コンテナ（Application）と連携し、全プラグインのライフサイクルを統括する。

## 実装内容

| ファイル | 役割 |
|---|---|
| `src/Core/Plugin/PluginInterface.php` | 全プラグインが実装すべきインターフェース定義 |
| `src/Core/Plugin/PluginMeta.php` | プラグインのメタ情報を保持するイミュータブルな値オブジェクト |
| `src/Core/Plugin/PluginManager.php` | プラグインのロード・登録・起動・インストール・アンインストールを管理するクラス |

## クラス設計

### PluginInterface

全プラグインが実装すべきインターフェース。
以下の5メソッドを定義する。

| メソッド | 呼び出しタイミング | 役割 |
|---|---|---|
| `meta()` | 任意 | プラグインの識別情報（ID・名前・バージョン等）を返す |
| `register(Application $app)` | `registerAll()` から呼ばれる | DI コンテナへのサービスバインドを行う |
| `boot(Application $app)` | `bootAll()` から呼ばれる | ルート・イベントリスナー等を登録する |
| `install(Application $app)` | `PluginManager::install()` から呼ばれる | テーブル作成など初回有効化処理を行う |
| `uninstall(Application $app)` | `PluginManager::uninstall()` から呼ばれる | テーブル削除など削除後処理を行う |

### PluginMeta

プラグインのメタ情報を保持する値オブジェクト。
`final class` かつ全プロパティが `readonly` のためイミュータブル。

| プロパティ | 型 | 説明 |
|---|---|---|
| `$id` | string | プラグインの一意な識別子（例: `blog-posts`） |
| `$name` | string | プラグインの表示名（例: `Blog Posts`） |
| `$version` | string | セマンティックバージョン文字列（例: `1.0.0`） |
| `$description` | string | プラグインの説明文 |
| `$author` | string | 作成者名または組織名 |
| `$entryFile` | string | `plugin.php` の絶対パス |

### PluginManager

| メソッド | 説明 |
|---|---|
| `loadFromDirectory(string $pluginsDir)` | 指定ディレクトリのサブディレクトリを走査し、`plugin.php` を require してプラグインをロードする |
| `register(PluginInterface $plugin)` | プラグインインスタンスを内部リストに手動登録する |
| `registerAll(Application $app)` | 全プラグインの `register()` を順番に呼び出す |
| `bootAll(Application $app)` | 全プラグインの `boot()` を順番に呼び出す |
| `install(string $pluginId, Application $app)` | 指定 ID のプラグインの `install()` を呼び出す |
| `uninstall(string $pluginId, Application $app)` | 指定 ID のプラグインの `uninstall()` を呼び出す |
| `getActive()` | 登録済みプラグインの連想配列を返す（キー: プラグイン ID） |

## プラグインのディレクトリ規約

```
plugins/
└── {plugin-id}/
    ├── plugin.php      # PluginInterface を実装したインスタンスを return する
    └── src/
        └── ...
```

`plugin.php` の形式:

```php
<?php

use TightPress\Plugin\BlogPosts\BlogPostPlugin;

return new BlogPostPlugin();
```

## ライフサイクルの実行順序

bootstrap.php では以下の順で呼び出す。

1. `loadFromDirectory()` — プラグインをロードする
2. `registerAll()` — 全プラグインの DI バインドを行う
3. `bootAll()` — 全プラグインのルート・リスナー登録を行う

インストール・アンインストールは管理画面からの操作によって個別に呼び出す。

## エラー処理

| 状況 | 例外クラス |
|---|---|
| `plugin.php` の戻り値が `PluginInterface` を実装していない | `RuntimeException` |
| 指定 ID のプラグインが登録されていない（install/uninstall 時） | `InvalidArgumentException` |
