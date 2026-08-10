# フェーズ別実装計画

## Phase 1: テーマ描画 + 記事・固定ページの表示

### 目標

twentytwentythree テーマを使って以下が動作すること。

- トップページ（記事一覧）の表示
- 記事詳細ページの表示
- 固定ページの表示

### 実装タスク

#### 1-1. プロジェクト基盤

- [ ] `composer.json` 作成（PSR-4 オートロード設定）
- [ ] `bootstrap.php` 作成（DI コンテナ初期化）
- [ ] `config/app.php` / `config/database.php` 作成
- [ ] `index.php` エントリポイント作成

#### 1-2. コア：データベース

- [ ] `ConnectionInterface` / `Connection` 実装
- [ ] `MySQLDriver` / `SQLiteDriver` / `PostgreSQLDriver` 実装
- [ ] `QueryBuilder` 実装
- [ ] マイグレーション機能の実装（`options` / `users` / `taxonomies` / `terms` テーブル）

#### 1-3. コア：HTTP

- [ ] `Request::fromGlobals()` 実装
- [ ] `Response` 実装
- [ ] `Router` 実装（GET パターンマッチ）

#### 1-4. コア：イベントシステム

- [ ] `ListenerProvider` 実装
- [ ] `EventDispatcher` 実装（PSR-14）
- [ ] WordPress 互換レイヤー（`do_action` / `apply_filters` グローバル関数）

#### 1-5. コア：テンプレートエンジン

- [ ] `ThemeLoader` 実装（テーマディレクトリの検出）
- [ ] `TemplateEngine` 実装（テンプレート階層解決 + 描画）
- [ ] `TemplateContext` 実装

#### 1-6. WordPress 互換テンプレートタグ

twentytwentythree テーマで使われているテンプレートタグを最低限実装する。

- [ ] `get_header()` / `get_footer()` / `get_template_part()`
- [ ] `wp_head()` / `wp_footer()`
- [ ] `bloginfo()` / `get_bloginfo()`
- [ ] `the_title()` / `get_the_title()`
- [ ] `the_content()` / `get_the_content()`
- [ ] `the_permalink()` / `get_permalink()`
- [ ] `the_post_thumbnail()` / `has_post_thumbnail()`
- [ ] `the_excerpt()` / `get_the_excerpt()`
- [ ] `the_date()` / `get_the_date()`
- [ ] `the_author()` / `get_the_author()`
- [ ] `have_posts()` / `the_post()` / `get_posts()`
- [ ] `is_home()` / `is_single()` / `is_page()` / `is_archive()`
- [ ] `body_class()` / `post_class()`
- [ ] `wp_enqueue_script()` / `wp_enqueue_style()` / `wp_print_scripts()` / `wp_print_styles()`
- [ ] `esc_html()` / `esc_attr()` / `esc_url()`
- [ ] `__()` / `_e()` / `_x()` （翻訳関数・最小実装）

#### 1-7. コア：プラグインマネージャー

- [ ] `PluginInterface` 定義
- [ ] `PluginMeta` 定義
- [ ] `PluginManager` 実装

#### 1-8. blog-posts プラグイン

- [ ] テーブル作成マイグレーション（`posts` / `post_meta` / `post_term_relationships` / `comments`）
- [ ] `Post` エンティティ
- [ ] `PostRepository` 実装
- [ ] ルート登録（`/` 一覧、`/posts/{slug}` 詳細）
- [ ] `PostController` 実装

#### 1-9. pages プラグイン

- [ ] テーブル作成マイグレーション（`pages` / `page_meta`）
- [ ] `Page` エンティティ
- [ ] `PageRepository` 実装
- [ ] ルート登録（`/{slug}` 固定ページ表示）
- [ ] `PageController` 実装

#### 1-10. シードデータ

- [ ] サンプル記事データの投入スクリプト
- [ ] サンプル固定ページデータの投入スクリプト

### 完了条件

- `php -S localhost:8080 index.php` で起動できる
- `http://localhost:8080/` でトップページ（記事一覧）が表示される
- `http://localhost:8080/posts/hello-world` で記事詳細が表示される
- `http://localhost:8080/about` で固定ページが表示される
- twentytwentythree テーマのスタイルが適用されている

---

## Phase 2: ユーザー認証

### 目標

ログイン・ログアウトができること。

### 実装タスク

- [ ] `users` テーブルマイグレーション
- [ ] `sessions` テーブルマイグレーション
- [ ] `PasswordHasher` 実装（`password_hash` / `password_verify` ラッパー）
- [ ] `AuthManager` 実装（ログイン・ログアウト・認証チェック）
- [ ] セッションミドルウェア実装
- [ ] 認証ミドルウェア実装（未ログイン時リダイレクト）
- [ ] ログインフォームルート (`GET /login`)
- [ ] ログイン処理ルート (`POST /login`)
- [ ] ログアウトルート (`POST /logout`)
- [ ] CSRF 保護ミドルウェア実装

### 完了条件

- `/login` でログインフォームが表示される
- 正しい認証情報でログインするとリダイレクトされる
- ログアウトするとセッションが破棄される

---

## Phase 3: 管理画面ログイン

### 目標

`/admin` にアクセスしてログインできること。

### 実装タスク

- [ ] 管理画面ルーター（`/admin/*`）
- [ ] 管理画面ログインページ (`GET /admin/login`)
- [ ] 管理画面ログイン処理 (`POST /admin/login`)
- [ ] 管理画面ダッシュボード (`GET /admin`)
- [ ] 管理者権限チェックミドルウェア
- [ ] 管理画面ベーステンプレート

### 完了条件

- `/admin/login` でログインフォームが表示される
- 管理者アカウントでログインするとダッシュボードが表示される
- 非管理者ユーザーはダッシュボードにアクセスできない

---

## Phase 4: 管理画面 CRUD

### 目標

管理画面から記事と固定ページを操作できること。

### 実装タスク

#### 記事管理（blog-posts プラグイン拡張）

- [ ] 記事一覧 (`GET /admin/posts`)
- [ ] 記事新規作成フォーム (`GET /admin/posts/new`)
- [ ] 記事作成処理 (`POST /admin/posts`)
- [ ] 記事編集フォーム (`GET /admin/posts/{id}/edit`)
- [ ] 記事更新処理 (`POST /admin/posts/{id}`)
- [ ] 記事削除処理 (`POST /admin/posts/{id}/delete`)
- [ ] ブロックエディター or シンプルテキストエリア（Phase 4 はシンプルで可）

#### 固定ページ管理（pages プラグイン拡張）

- [ ] 固定ページ一覧 (`GET /admin/pages`)
- [ ] 固定ページ新規作成 (`GET /admin/pages/new`)
- [ ] 固定ページ作成処理 (`POST /admin/pages`)
- [ ] 固定ページ編集 (`GET /admin/pages/{id}/edit`)
- [ ] 固定ページ更新処理 (`POST /admin/pages/{id}`)
- [ ] 固定ページ削除処理 (`POST /admin/pages/{id}/delete`)

### 完了条件

- 記事の新規作成・編集・削除がフロントエンドに反映される
- 固定ページの新規作成・編集・削除がフロントエンドに反映される

---

## Phase 5: プラグイン管理

### 目標

管理画面からプラグインのインストール・アンインストールができること。

### 実装タスク

- [ ] プラグイン一覧 (`GET /admin/plugins`)
- [ ] プラグインアップロード (`POST /admin/plugins/upload`)
- [ ] ZIP 展開処理
- [ ] `PluginManager::install()` の UI 連携
- [ ] プラグイン有効化/無効化の切り替え
- [ ] `PluginManager::uninstall()` の UI 連携
- [ ] プラグインディレクトリの削除

### 完了条件

- ZIP ファイルをアップロードしてプラグインをインストールできる
- インストールしたプラグインが動作する
- アンインストールするとプラグインが無効になる
