# コアHTTP層 (`src/Core/Http/`)

TightPress のHTTPリクエスト・レスポンス処理を担うコア層。PSR-7 に依存せずシンプルな値オブジェクトとして実装されている。

## ファイル構成

```
src/Core/Http/
├── Request.php               # HTTPリクエストをラップする読み取り専用クラス
├── Response.php              # イミュータブルなHTTPレスポンスクラス
├── Router.php                # URLパターンベースのルータークラス
└── Middleware/
    ├── MiddlewareInterface.php   # ミドルウェアインターフェース（Phase 1 はスタブのみ）
    └── Pipeline.php              # ミドルウェアパイプライン（Phase 1 はスタブのみ）
```

## クラス一覧

### `Request`

グローバル変数（`$_SERVER`, `$_GET`, `$_POST`, `$_COOKIE`, `$_FILES`）を読み取り専用プロパティとして保持する値オブジェクト。

#### プロパティ

| プロパティ | 型     | 説明                                                  |
|-----------|--------|-------------------------------------------------------|
| `$method`  | string | HTTPメソッド（大文字。例: `'GET'`）                   |
| `$uri`     | string | リクエストURI（クエリ含む。例: `'/posts/a?foo=bar'`） |
| `$path`    | string | パス部分のみ（例: `'/posts/a'`）                      |
| `$query`   | array  | クエリパラメーター（`$_GET` の内容）                  |
| `$input`   | array  | リクエストボディ（`$_POST` または JSON デコード結果） |
| `$headers` | array  | 正規化されたHTTPヘッダー連想配列                      |
| `$cookies` | array  | クッキー（`$_COOKIE` の内容）                         |
| `$files`   | array  | アップロードファイル（`$_FILES` の内容）              |

#### ファクトリメソッド

```php
// 本番環境: グローバル変数から生成する
$request = Request::fromGlobals();

// テスト環境: 任意の値から生成する
$request = Request::create('GET', '/posts/hello-world', query: ['page' => '1']);
```

#### ヘッダーの正規化

`$_SERVER` の `HTTP_*` キーを `Content-Type` 形式に変換する。

```
HTTP_CONTENT_TYPE     → Content-Type
HTTP_ACCEPT_ENCODING  → Accept-Encoding
```

---

### `Response`

イミュータブルなHTTPレスポンスクラス。`with*` メソッドは常に新しいインスタンスを返す。

#### 基本的な使い方

```php
// メソッドチェーンで組み立てる
$response = (new Response())
    ->withStatus(200)
    ->withHeader('X-Custom', 'value')
    ->withBody('<p>Hello</p>');

// ブラウザに送信する
$response->send();
```

#### ショートカットメソッド

```php
// HTMLレスポンス（デフォルト 200）
$res = Response::html('<h1>Hello</h1>');
$res = Response::html('<h1>Created</h1>', 201);

// リダイレクト（デフォルト 302）
$res = Response::redirect('/login');
$res = Response::redirect('/dashboard', 301);

// 404 Not Found
$res = Response::notFound();

// JSON レスポンス
$res = Response::json(['key' => 'value']);
```

---

### `Router`

URLパターンにコールバックハンドラを結びつけるルータークラス。プレースホルダー `{name}` 形式で動的なURLパラメーターを受け取れる。

#### 使い方

```php
$router = new Router();

// ルートを登録する
$router->get('/', function (Request $req, array $params): Response {
    return Response::html('<h1>トップページ</h1>');
});

$router->get('/posts/{slug}', function (Request $req, array $params): Response {
    $slug = $params['slug']; // マッチした値が入る
    return Response::html("<h1>記事: {$slug}</h1>");
});

$router->post('/posts', function (Request $req, array $params): Response {
    $title = $req->input['title'] ?? '';
    return Response::html("<p>{$title} を作成しました</p>", 201);
});

// リクエストをディスパッチしてレスポンスを取得する
$response = $router->dispatch(Request::fromGlobals());
$response->send();
```

#### プレースホルダーのパターン変換

```
/posts/{slug}           → #^/posts/(?P<slug>[^/]+)$#u
/users/{id}/posts/{no}  → #^/users/(?P<id>[^/]+)/posts/(?P<no>[^/]+)$#u
```

プレースホルダーはスラッシュ（`/`）を除く1文字以上にマッチする。

---

### `MiddlewareInterface`（Phase 1 スタブ）

ミドルウェアが実装すべきインターフェース。Phase 1 では定義のみで、実装クラスは Phase 2 以降に追加予定。

```php
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
```

---

### `Pipeline`（Phase 1 スタブ）

ミドルウェアをチェーンして実行するパイプラインクラス。Phase 1 では直接ハンドラを呼ぶスタブ実装。

```php
$pipeline = new Pipeline();

// Phase 2 以降でミドルウェアを追加する
// $pipeline->pipe(new AuthMiddleware());

// リクエストをパイプラインに通す（Phase 1 では直接ハンドラを呼ぶ）
$response = $pipeline->run($request, function (Request $req): Response {
    return Response::html('<p>Hello</p>');
});
```

## 設計方針

- **PSR-7 非依存:** 外部依存を最小にするため、PSR-7 インターフェースは使用しない。
- **イミュータブル設計:** `Response` の `with*` メソッドはクローンを返すため、副作用が少ない。
- **シンプルなルーティング:** 正規表現ベースのパターンマッチングで、named capture group によりパラメーター名と値を安全に対応させる。
- **テスト容易性:** `Request::create()` ファクトリで任意の値から生成できるため、グローバル変数不要でユニットテストが書ける。

## 今後の予定（Phase 2 以降）

- `MiddlewareInterface` の実装クラス（認証・ロギング・キャッシュ等）
- `Pipeline` でのミドルウェア連鎖処理の本実装
- `Router` でのメソッドオーバーライド対応（`_method` フィールドによる PUT/DELETE 擬似実装）
