# [Phase 1] 1-8. blog-posts プラグイン

## 概要

ブログ記事の取得・表示を担う blog-posts プラグインを実装した。
記事一覧（GET /）と記事詳細（GET /posts/{slug}）のルートを提供する。

---

## ファイル構成

```
plugins/blog-posts/
├── plugin.php                         # エントリポイント（BlogPostPlugin インスタンスを返す）
└── src/
    ├── BlogPostPlugin.php             # PluginInterface 実装・サービス登録・ルート登録
    ├── Model/
    │   ├── Post.php                   # 記事エンティティ（イミュータブルな値オブジェクト）
    │   └── PostRepository.php         # posts テーブルへの CRUD 操作
    ├── Http/
    │   └── PostController.php         # 記事一覧・詳細のリクエスト処理
    ├── Migration/
    │   └── CreatePostsTables.php      # 4テーブルの作成・削除マイグレーション
    └── Events/
        ├── PostCreated.php            # 記事作成ドメインイベント
        └── PostUpdated.php            # 記事更新ドメインイベント
```

---

## データベーススキーマ

### posts テーブル

ブログ記事の基本情報を格納する。

| カラム         | 型           | 説明                                    |
|--------------|-------------|----------------------------------------|
| id           | INTEGER      | 主キー（AUTOINCREMENT）                   |
| author_id    | INTEGER      | 投稿者の users.id（削除時 NULL）            |
| slug         | VARCHAR(191) | URLスラッグ（UNIQUE制約）                  |
| title        | VARCHAR(500) | 記事タイトル                               |
| content      | TEXT         | 記事本文（HTML）                           |
| excerpt      | TEXT         | 記事抜粋                                  |
| status       | VARCHAR(20)  | 公開ステータス（draft / publish 等）        |
| comment_status | VARCHAR(20) | コメント受付状態（open / closed）          |
| thumbnail_id | INTEGER      | アイキャッチ画像の media.id（削除時 NULL） |
| published_at | DATETIME     | 公開日時（未公開時は NULL）                 |
| created_at   | DATETIME     | 作成日時                                  |
| updated_at   | DATETIME     | 更新日時                                  |

インデックス:
- `idx_posts_status` : status カラム（公開ステータスによる絞り込み用）
- `idx_posts_published_at` : published_at カラム（公開日時降順ソート用）

### post_meta テーブル

記事に紐付くカスタムメタデータを格納する（WordPress の `post_meta` 相当）。

| カラム      | 型           | 説明                      |
|-----------|-------------|--------------------------|
| id        | INTEGER      | 主キー（AUTOINCREMENT）   |
| post_id   | INTEGER      | posts.id への外部キー     |
| meta_key  | VARCHAR(191) | メタデータのキー           |
| meta_value | TEXT        | メタデータの値             |
| created_at | DATETIME    | 作成日時                  |
| updated_at | DATETIME    | 更新日時                  |

インデックス:
- `idx_post_meta_post_id_key` : (post_id, meta_key) の複合インデックス

### post_term_relationships テーブル

記事とタクソノミータームの中間テーブル（カテゴリー・タグの紐付け）。

| カラム    | 型      | 説明                    |
|---------|--------|------------------------|
| post_id | INTEGER | posts.id への外部キー   |
| term_id | INTEGER | terms.id への外部キー   |

- 主キー: (post_id, term_id) の複合主キー
- インデックス: `idx_post_term_relationships_term_id`

### comments テーブル

記事に投稿されたコメントを格納する。

| カラム        | 型           | 説明                               |
|-------------|-------------|-----------------------------------|
| id          | INTEGER      | 主キー（AUTOINCREMENT）            |
| post_id     | INTEGER      | posts.id への外部キー              |
| parent_id   | INTEGER      | 親コメントの comments.id（返信用） |
| author_id   | INTEGER      | 投稿者の users.id（ゲスト時 NULL） |
| author_name | VARCHAR(255) | コメント投稿者名                    |
| author_email | VARCHAR(100) | コメント投稿者メールアドレス        |
| content     | TEXT         | コメント本文                        |
| status      | VARCHAR(20)  | 承認状態（pending / approved 等）  |
| created_at  | DATETIME     | 作成日時                            |
| updated_at  | DATETIME     | 更新日時                            |

インデックス:
- `idx_comments_post_id` : post_id カラム
- `idx_comments_status` : status カラム

---

## クラス設計

### BlogPostPlugin

`PluginInterface` を実装するプラグインのメインクラス。

| メソッド          | 説明                                                   |
|----------------|-------------------------------------------------------|
| `meta()`        | プラグインメタ情報（id='blog-posts', version='1.0.0'） |
| `register($app)` | PostRepository をシングルトンで DI コンテナに登録する  |
| `boot($app)`    | GET / と GET /posts/{slug} をルーターに登録する       |
| `install($app)` | CreatePostsTables::up() でテーブルを作成する          |
| `uninstall($app)` | CreatePostsTables::down() でテーブルを削除する      |

### Post エンティティ

posts テーブルの1行を表すイミュータブルな値オブジェクト。
PHP 8.1 の readonly プロパティを使用して不変性を保証する。

### PostRepository

posts テーブルへの CRUD 操作をカプセル化するリポジトリクラス。

| メソッド                                    | 説明                                        |
|------------------------------------------|-------------------------------------------|
| `findBySlug(string $slug): ?Post`        | スラッグで記事を取得する                     |
| `findById(int $id): ?Post`              | ID で記事を取得する                          |
| `findPublished(int $limit, int $offset)` | status='publish' の記事を published_at 降順で取得 |
| `save(Post $post): Post`                | id=0 なら INSERT、それ以外は UPDATE         |
| `delete(int $id): void`                 | 記事を削除する                               |
| `hydrate(array $row): Post`（private）  | 行データを Post エンティティに変換する        |

### PostController

記事一覧・詳細のリクエストを処理するコントローラークラス。

| メソッド                                          | ルート            | 説明                              |
|------------------------------------------------|-----------------|----------------------------------|
| `index(Request $request, array $params)`       | GET /           | 記事一覧を home.php/index.php で描画 |
| `show(Request $request, array $params)`        | GET /posts/{slug} | 記事詳細を single-post.php 等で描画 |

### CreatePostsTables

ブログ記事関連テーブルのマイグレーションクラス。SQLite 互換の DDL を使用する。

| メソッド      | 説明                                           |
|------------|-----------------------------------------------|
| `up()`     | 4テーブルを CREATE TABLE IF NOT EXISTS で作成する |
| `down()`   | 依存順序（子→親）で DROP TABLE IF EXISTS を実行する |

### PostCreated / PostUpdated イベント

記事作成・更新時に発行されるドメインイベント。
他のプラグインは `EventDispatcher` を通じてこれらのイベントを購読できる。

---

## テンプレート階層

| ルート            | 試行するテンプレート（優先順）                                   |
|-----------------|-------------------------------------------------------------|
| GET /           | `home.php` → `index.php`                                    |
| GET /posts/{slug} | `single-post.php` → `single.php` → `singular.php` → `index.php` |

---

## 使い方

### プラグインの登録

`bootstrap.php` で PluginManager にプラグインを登録する。

```php
$pluginManager->register(require BASE_PATH . '/plugins/blog-posts/plugin.php');
```

### テーブルの作成（インストール）

```php
$pluginManager->install('blog-posts', $app);
```

### テンプレートから記事データにアクセスする

テーマテンプレート内では `TemplateContext` を通じて記事データにアクセスする。

```php
// TemplateContext を取得する
$context = $app->make(TemplateContext::class);

// 記事一覧を取得する
foreach ($context->posts as $post) {
    echo htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8');
}

// 現在の記事を取得する（記事詳細ページの場合）
$currentPost = $context->currentPost;
```

---

## 技術的な補足

- ConnectionInterface を直接使用し、QueryBuilder には依存しない設計とした。これにより PostRepository の単体テストで ConnectionInterface のモックを容易に差し込める。
- `hydrate()` メソッドは private スコープとし、外部から行データを直接渡してエンティティを生成させない設計にした。
- `save()` メソッドは INSERT 後に `findById()` を呼び直すことで、DB が自動設定した値（created_at 等）を含む最新状態の Post エンティティを返す。
- SQLite では CREATE TABLE 内に INDEX 定義を書けないため、インデックスは `CREATE INDEX IF NOT EXISTS` を別途実行する。
- コメントの `parent_id` は自己参照外部キーにより、ネストされた返信コメントの階層構造を表現できる。
