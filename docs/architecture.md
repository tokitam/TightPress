# アーキテクチャ設計

## 全体構成

```
HTTP Request
    │
    ▼
index.php（エントリポイント）
    │
    ▼
bootstrap.php
    ├─ Composer オートロード
    ├─ 設定ファイル読み込み（config/）
    ├─ DIコンテナ構築（Application）
    ├─ DBコネクション確立
    ├─ プラグインマネージャー起動 → 各プラグインの boot()
    └─ イベントディスパッチャー初期化
    │
    ▼
Router（URLパターンマッチ）
    │
    ▼
Middleware Pipeline
    ├─ セッション処理
    ├─ 認証チェック（必要な場合）
    └─ ...
    │
    ▼
Controller（プラグインまたはコアが登録）
    │
    ▼
TemplateEngine（テーマ描画）
    │
    ▼
HTTP Response
```

---

## DIコンテナ（Application クラス）

WordPress のグローバル変数や `global $wpdb` を廃止し、
依存性の注入（DI）で全サービスを管理する。

```php
namespace TightPress\Core;

class Application
{
    // バインディング（抽象 → 具体）を保持
    private array $bindings = [];

    // シングルトンインスタンス
    private array $instances = [];

    /**
     * サービスをバインドする
     *
     * @param string   $abstract  インターフェース名または識別子
     * @param callable $factory   生成ファクトリ
     */
    public function bind(string $abstract, callable $factory): void;

    /**
     * シングルトンとしてバインドする
     */
    public function singleton(string $abstract, callable $factory): void;

    /**
     * サービスを解決する
     *
     * @template T
     * @param class-string<T> $abstract
     * @return T
     */
    public function make(string $abstract): mixed;
}
```

**使用例（bootstrap.php）:**

```php
$app = new Application();

$app->singleton(ConnectionInterface::class, function (Application $app) {
    $config = $app->make(Config::class);
    return Connection::create($config->get('database'));
});

$app->singleton(EventDispatcherInterface::class, function (Application $app) {
    return new EventDispatcher($app->make(ListenerProvider::class));
});
```

---

## HTTPレイヤー

PSR-7 には依存せず、シンプルな独自実装を採用する（外部ライブラリへの依存を最小化）。
将来的に PSR-7 アダプターを追加できる構造にしておく。

### Request

```php
namespace TightPress\Core\Http;

class Request
{
    public readonly string $method;
    public readonly string $uri;
    public readonly string $path;
    public readonly array  $query;    // $_GET
    public readonly array  $input;    // $_POST / JSON body
    public readonly array  $headers;
    public readonly array  $cookies;
    public readonly array  $files;

    public static function fromGlobals(): self;
}
```

### Router

ルート定義は「パターン + HTTP メソッド + ハンドラ」の三つ組み。
プラグインはブート時に自分のルートを登録する。

```php
namespace TightPress\Core\Http;

class Router
{
    /**
     * GETルートを登録する
     *
     * @param string   $pattern   URLパターン（例: '/posts/{slug}'）
     * @param callable $handler
     */
    public function get(string $pattern, callable $handler): void;

    public function post(string $pattern, callable $handler): void;

    /**
     * リクエストにマッチするハンドラを解決して実行する
     */
    public function dispatch(Request $request): Response;
}
```

---

## テンプレートエンジン

WordPress テーマの PHP テンプレートをそのまま動作させることを目標とする。
テーマ内の `get_header()` / `the_post()` など、WordPress テンプレートタグを
TightPress コアで実装する互換レイヤーとして提供する。

```php
namespace TightPress\Core\Template;

class TemplateEngine
{
    /**
     * テーマテンプレートファイルを読み込んで描画する
     *
     * @param string              $template  テンプレートパス（テーマルートからの相対）
     * @param TemplateContext      $context   テンプレートに渡す変数群
     */
    public function render(string $template, TemplateContext $context): string;

    /**
     * テンプレートファイルの優先順位を解決する（WordPressのテンプレート階層に相当）
     *
     * @param  string[] $candidates  優先順に並んだテンプレートファイル名
     * @return string                最初に見つかったテンプレートの絶対パス
     */
    public function resolve(array $candidates): string;
}
```

### テンプレート階層（Phase 1 最低限）

| URLパターン | 試行するテンプレート（上から順） |
|---|---|
| 記事一覧 (`/`) | `home.php` → `index.php` |
| 記事詳細 (`/posts/{slug}`) | `single-post.php` → `single.php` → `singular.php` → `index.php` |
| 固定ページ (`/{slug}`) | `page-{slug}.php` → `page.php` → `singular.php` → `index.php` |

---

## データアクセス層

### QueryBuilder

生の PDO クエリをラップし、SQLite / MySQL / PostgreSQL の方言差を吸収する。
ORM は導入せず、薄いクエリビルダーにとどめる。

```php
namespace TightPress\Core\Database;

class QueryBuilder
{
    /**
     * SELECT クエリを開始する
     *
     * @param string $table テーブル名
     */
    public function table(string $table): self;

    public function select(string ...$columns): self;
    public function where(string $column, mixed $value): self;
    public function orderBy(string $column, string $direction = 'ASC'): self;
    public function limit(int $limit): self;
    public function offset(int $offset): self;

    /**
     * 結果を配列で返す
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(): array;

    /**
     * 1件だけ返す
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array;

    /**
     * レコードを挿入して挿入IDを返す
     *
     * @param  array<string, mixed> $data
     */
    public function insert(array $data): int;

    public function update(array $data): int;
    public function delete(): int;
}
```

### Repository パターン

各エンティティごとに Repository クラスを設ける。
プラグインは自分のエンティティの Repository を自分で定義する。

```php
// コアが提供するベースインターフェース
namespace TightPress\Core\Database;

interface RepositoryInterface
{
    public function findById(int $id): ?object;
    public function save(object $entity): void;
    public function delete(int $id): void;
}
```

---

## セキュリティ方針

| 項目 | 対応方針 |
|---|---|
| SQLインジェクション | QueryBuilder がプリペアドステートメントを使用 |
| XSS | テンプレート出力時に `htmlspecialchars()` エスケープを強制 |
| CSRF | POST フォームに CSRF トークンを自動付与するミドルウェアを提供 |
| パスワード | `password_hash()` / `password_verify()` を使用（PHP 8 標準） |
| セッション | `session.cookie_httponly` / `session.cookie_secure` を設定 |
