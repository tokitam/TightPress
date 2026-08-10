# [Phase 1] 1-9. pages プラグイン

## 概要

固定ページの取得・表示を担う pages プラグインを実装した。
`/{slug}` のルートを提供し、blog-posts プラグインより低優先度で登録される。

## 実装ファイル

| ファイル | 役割 |
|---|---|
| `plugins/pages/plugin.php` | プラグインエントリポイント（PagesPlugin インスタンスを返す） |
| `plugins/pages/src/PagesPlugin.php` | プラグインメインクラス（PluginInterface を実装） |
| `plugins/pages/src/Model/Page.php` | 固定ページエンティティ（イミュータブルな値オブジェクト） |
| `plugins/pages/src/Model/PageRepository.php` | 固定ページリポジトリ（pages テーブルへの CRUD 操作） |
| `plugins/pages/src/Http/PageController.php` | 固定ページコントローラー（GET /{slug} を処理） |
| `plugins/pages/src/Migration/CreatePagesTables.php` | テーブル作成・削除マイグレーション |

## データベーススキーマ

### pages テーブル

| カラム | 型 | 説明 |
|---|---|---|
| `id` | INTEGER PRIMARY KEY | ページID（自動採番） |
| `parent_id` | INTEGER | 親ページID（外部キー、削除時は NULL に設定） |
| `author_id` | INTEGER | 投稿者ユーザーID（外部キー、削除時は NULL に設定） |
| `slug` | VARCHAR(191) UNIQUE | URLスラッグ（一意制約あり） |
| `title` | VARCHAR(500) | ページタイトル |
| `content` | LONGTEXT | ページ本文（HTML） |
| `status` | VARCHAR(20) | 公開ステータス（draft / publish 等、デフォルト: draft） |
| `thumbnail_id` | INTEGER | アイキャッチ画像のメディアID（外部キー） |
| `sort_order` | INTEGER | 同一階層内での表示順序（デフォルト: 0） |
| `template` | VARCHAR(255) | 個別テンプレートファイル名（任意） |
| `published_at` | DATETIME | 公開日時（未公開時は NULL） |
| `created_at` | DATETIME | 作成日時 |
| `updated_at` | DATETIME | 更新日時 |

### page_meta テーブル

| カラム | 型 | 説明 |
|---|---|---|
| `id` | INTEGER PRIMARY KEY | メタデータID（自動採番） |
| `page_id` | INTEGER | ページID（外部キー、削除時はカスケード削除） |
| `meta_key` | VARCHAR(191) | メタデータキー |
| `meta_value` | TEXT | メタデータ値 |
| `created_at` | DATETIME | 作成日時 |
| `updated_at` | DATETIME | 更新日時 |

## クラス設計

### PagesPlugin

`PluginInterface` を実装したプラグインメインクラス。

- `meta()`: プラグインのメタ情報（id='pages', name='Pages', version='1.0.0'）を返す
- `register()`: `PageRepository` をシングルトンで DI コンテナに登録する
- `boot()`: Router に `GET /{slug}` を登録する（blog-posts より後に登録されるため優先度が低い）
- `install()`: `CreatePagesTables::up()` でテーブルを作成する
- `uninstall()`: `CreatePagesTables::down()` でテーブルを削除する

### Page

`pages` テーブルの1行を表すイミュータブルな値オブジェクト。
全プロパティは `readonly` で、コンストラクタインジェクションでのみ値を設定できる。

### PageRepository

`pages` テーブルへの CRUD 操作をカプセル化するリポジトリクラス。

| メソッド | 説明 |
|---|---|
| `findBySlug(string $slug): ?Page` | スラッグで固定ページを1件取得する |
| `findById(int $id): ?Page` | IDで固定ページを1件取得する |
| `findPublished(): array` | 公開済み固定ページを sort_order ASC で全件取得する |
| `save(Page $page): Page` | 固定ページを保存する（id=0 で INSERT、それ以外は UPDATE） |
| `delete(int $id): void` | 固定ページを削除する |

### PageController

`GET /{slug}` のリクエストを処理するコントローラークラス。

テンプレートの解決順序:
1. `page-{slug}.php`（スラッグ固有テンプレート）
2. ページに設定された個別テンプレート（`$page->template`）
3. `page.php`（固定ページ共通テンプレート）
4. `singular.php`（個別コンテンツ共通テンプレート）
5. `index.php`（フォールバック）

`Page` エンティティを `TemplateContext::$currentPost` に格納することで、
WordPress 互換関数がページ情報を参照できるようにしている。

### CreatePagesTables

pages / page_meta テーブルの作成・削除を行うマイグレーションクラス。
SQLite 互換の DDL を使用する。

- `up()`: `CREATE TABLE IF NOT EXISTS` でテーブルを作成する
- `down()`: `DROP TABLE IF EXISTS` でテーブルを削除する（page_meta → pages の順）

## ルート登録の設計

`/{slug}` ルートは汎用的なパターンであるため、blog-posts プラグインの
`/posts/{slug}` や `/` より後に登録することで衝突を防ぐ設計となっている。

Router の実装によりルートは登録順に評価されるため、
このプラグインが他のプラグインより後にロードされることが前提となる。

## 使い方

1. `PagesPlugin::install()` を呼び出してテーブルを作成する
2. 直接 SQL でページデータを INSERT するか、`PageRepository::save()` を使用する
3. ブラウザで `http://localhost:8080/{slug}` にアクセスすると固定ページが表示される
