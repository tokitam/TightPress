# コアテンプレートエンジン

## 概要

WordPress テーマのテンプレートファイルを読み込んで描画するエンジン。
テンプレート階層に基づくファイル解決と、have_posts / the_post 相当のループ制御を提供する。

## クラス構成

| クラス / 列挙型 | ファイル | 役割 |
|---|---|---|
| `PageType` | `src/Core/Template/PageType.php` | ページ種別を表す列挙型 |
| `ThemeLoader` | `src/Core/Template/ThemeLoader.php` | テーマディレクトリのパス・URL 管理 |
| `TemplateContext` | `src/Core/Template/TemplateContext.php` | ループ制御とテンプレートコンテキスト管理 |
| `TemplateEngine` | `src/Core/Template/TemplateEngine.php` | テンプレート解決と描画 |

## PageType

現在のリクエストがどのページ種別かを表す列挙型。

```php
PageType::Home;     // 記事一覧（ホーム）
PageType::Single;   // 記事詳細
PageType::Page;     // 固定ページ
PageType::NotFound; // 404
```

## ThemeLoader

テーマディレクトリのパスと URL を管理する。

```php
$loader = new ThemeLoader('/path/to/themes', 'my-theme');

$loader->getThemeDir();              // /path/to/themes/my-theme
$loader->path('header.php');         // /path/to/themes/my-theme/header.php
$loader->url('style.css', 'https://example.com'); // https://example.com/themes/my-theme/style.css
$loader->getActiveTheme();           // my-theme
```

## TemplateContext

WordPress の have_posts() / the_post() に相当するループ制御を提供する。

```php
$context = new TemplateContext();
$context->pageType = PageType::Home;
$context->posts = [$post1, $post2, $post3];

// ループ
while ($context->hasPosts()) {
    $context->advanceCursor();
    // $context->currentPost に現在の投稿が入る
}

// 再ループが必要な場合
$context->rewindPosts();
```

### `toArray()` で展開される変数

| 変数名 | 内容 |
|---|---|
| `$tp_context` | `TemplateContext` インスタンス本体 |
| `$posts` | 投稿一覧（`$context->posts` と同じ） |

## TemplateEngine

テンプレートファイルの解決と描画を担当する。

### テンプレート解決

WordPress のテンプレート階層と同様に、優先度の高いファイル名から順に候補を渡す。

```php
$engine = new TemplateEngine($loader);

// 最初に見つかったファイルの絶対パスを返す
$path = $engine->resolve([
    'single-post.php',
    'single.php',
    'index.php',
]);
```

空文字はスキップされる（カスタムテンプレートが未設定の場合の考慮）。
1件も見つからない場合は `RuntimeException` を投げる。

### 描画

```php
$html = $engine->render($path, $context);
```

- `extract($context->toArray(), EXTR_SKIP)` でコンテキスト変数をテンプレート内に展開する
- 出力バッファリングで echo/print の出力を HTML 文字列としてキャプチャする
- テンプレート内で例外・エラーが発生した場合はバッファを破棄して例外を再スローする
