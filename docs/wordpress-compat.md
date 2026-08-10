# WordPress 互換レイヤー設計

## 方針

twentytwentythree テーマは WordPress テンプレートタグを前提に書かれている。
Phase 1 ではこれらのテンプレートタグをグローバル関数として提供する互換レイヤーを実装し、
テーマをほぼそのまま動作させることを目標とする。

互換関数は `src/Compat/` 以下に配置し、
コアシステムとは明確に分離する。

---

## グローバルコンテキスト管理

WordPress は `$wp_query` / `$post` / `$wp` などのグローバル変数で
現在のリクエスト状態を管理している。

TightPress では DI コンテナに登録した `TemplateContext` で代替する。
グローバル関数はコンテナから `TemplateContext` を取得して動作する。

```php
// src/Compat/context.php

/**
 * 現在のコンテキストを返すヘルパー
 */
function tp_context(): TemplateContext
{
    return app(TemplateContext::class);
}
```

---

## TemplateContext の構造

```php
namespace TightPress\Core\Template;

class TemplateContext
{
    // 現在表示中の投稿（single / page 時に設定）
    public ?object $currentPost = null;

    // 現在のクエリ結果（一覧ページ時に設定）
    /** @var list<object> */
    public array $posts = [];

    // ループカーソル
    private int $cursor = -1;

    // 現在のページ種別
    public PageType $pageType = PageType::Home;

    /**
     * the_post() の動作：ループを1件進める
     */
    public function advanceCursor(): bool;

    /**
     * have_posts() の動作：次の投稿があるか
     */
    public function hasPosts(): bool;
}
```

---

## 互換関数一覧（Phase 1 実装対象）

### ループ制御

| WordPress 関数 | 実装方針 |
|---|---|
| `have_posts()` | `tp_context()->hasPosts()` に委譲 |
| `the_post()` | `tp_context()->advanceCursor()` に委譲 |
| `get_posts($args)` | `PostRepository->findAll($args)` に委譲 |

### 投稿情報

| WordPress 関数 | 実装方針 |
|---|---|
| `get_the_title($id)` | `currentPost->title` を返す |
| `the_title()` | `get_the_title()` をエスケープして echo |
| `get_the_content()` | `FilterPostContent` イベントを通じたコンテンツを返す |
| `the_content()` | `get_the_content()` を echo（kses は未実装・Phase 1 では素通し） |
| `get_the_excerpt()` | `currentPost->excerpt` を返す（空なら content を切り詰め） |
| `the_excerpt()` | `get_the_excerpt()` を echo |
| `get_permalink($id)` | スラッグからURLを構築して返す |
| `the_permalink()` | `get_permalink()` をエスケープして echo |
| `the_date($format)` | `currentPost->published_at` をフォーマットして echo |
| `get_the_date($format)` | 文字列で返す |
| `the_author()` | `currentPost->author->display_name` を echo |
| `get_the_author()` | 文字列で返す |
| `has_post_thumbnail()` | `currentPost->thumbnail_id !== null` を返す |
| `the_post_thumbnail($size)` | `<img>` タグを出力（Phase 1 は size 無視可） |
| `get_post_thumbnail_id()` | `currentPost->thumbnail_id` を返す |
| `post_class($class)` | 投稿に応じた class 文字列を echo |
| `get_post_class($class)` | class 配列を返す |

### サイト情報

| WordPress 関数 | 実装方針 |
|---|---|
| `bloginfo($show)` | `options` テーブルから対応する値を返す |
| `get_bloginfo($show)` | 文字列で返す |
| `get_site_url()` | `options.siteurl` を返す |
| `home_url($path)` | サイトURL + パスを返す |
| `site_url($path)` | `home_url` と同じ（Phase 1 では同一視） |

### テンプレート分割

| WordPress 関数 | 実装方針 |
|---|---|
| `get_header($name)` | `header.php` または `header-{name}.php` を読み込む |
| `get_footer($name)` | `footer.php` または `footer-{name}.php` を読み込む |
| `get_sidebar($name)` | `sidebar.php` または `sidebar-{name}.php` を読み込む |
| `get_template_part($slug, $name)` | テーマから `{slug}-{name}.php` を探して読み込む |
| `locate_template($templates)` | テーマ内でファイルを探してパスを返す |

### アセット

| WordPress 関数 | 実装方針 |
|---|---|
| `wp_enqueue_style($handle, $src, $deps, $ver)` | キューに追加（`wp_head()` で出力） |
| `wp_enqueue_script($handle, $src, $deps, $ver, $in_footer)` | キューに追加（`wp_footer()` で出力） |
| `wp_dequeue_style($handle)` | キューから除去 |
| `wp_dequeue_script($handle)` | キューから除去 |
| `wp_print_styles()` | `<link>` タグを出力 |
| `wp_print_scripts()` | `<script>` タグを出力 |
| `get_stylesheet_uri()` | テーマの `style.css` のURLを返す |
| `get_theme_file_uri($file)` | テーマファイルの URL を返す |
| `get_theme_file_path($file)` | テーマファイルの絶対パスを返す |

### 条件分岐

| WordPress 関数 | 実装方針 |
|---|---|
| `is_home()` | `pageType === PageType::Home` |
| `is_front_page()` | Phase 1 では `is_home()` と同一視 |
| `is_single()` | `pageType === PageType::Single` |
| `is_page($slug)` | `pageType === PageType::Page`（slug 指定にも対応） |
| `is_archive()` | Phase 1 では常に `false` |
| `is_category()` | Phase 1 では常に `false` |
| `is_tag()` | Phase 1 では常に `false` |
| `is_404()` | `pageType === PageType::NotFound` |

### エスケープ

| WordPress 関数 | 実装方針 |
|---|---|
| `esc_html($text)` | `htmlspecialchars($text, ENT_QUOTES)` を返す |
| `esc_attr($text)` | `htmlspecialchars($text, ENT_QUOTES)` を返す |
| `esc_url($url)` | URL のサニタイズ（フィルターなし版） |
| `esc_js($text)` | JS 用エスケープ |
| `wp_kses($string, $allowed)` | Phase 1 では素通し（後で実装） |

### 翻訳

| WordPress 関数 | 実装方針 |
|---|---|
| `__($text, $domain)` | Phase 1 では `$text` をそのまま返す |
| `_e($text, $domain)` | `echo __($text, $domain)` |
| `_x($text, $context, $domain)` | Phase 1 では `$text` をそのまま返す |
| `esc_html__($text, $domain)` | `esc_html(__($text, $domain))` を返す |
| `esc_html_e($text, $domain)` | echo する版 |
| `esc_attr__($text, $domain)` | `esc_attr(__($text, $domain))` を返す |

### フック（互換）

| WordPress 関数 | 実装方針 |
|---|---|
| `add_action($hook, $callback, $priority)` | `ListenerProvider` に登録 |
| `do_action($hook, ...$args)` | `EventDispatcher::dispatch()` に委譲 |
| `add_filter($hook, $callback, $priority)` | `ListenerProvider` に登録 |
| `apply_filters($hook, $value, ...$args)` | フィルターイベントを発行して値を返す |
| `remove_action($hook, $callback)` | Phase 1 では未実装（ログ出力のみ） |
| `has_action($hook)` | Phase 1 では常に `false` |

---

## 互換レイヤーの段階的廃止方針

| フェーズ | 方針 |
|---|---|
| Phase 1 | 互換関数を全面的に使用。テーマをそのまま動作させることを優先 |
| Phase 2〜3 | 管理画面は互換関数を使わず TightPress ネイティブで実装 |
| Phase 4〜5 | ネイティブプラグイン API を整備し、新規プラグインは互換関数に依存しない |
| 将来 | 互換レイヤーをオプション化し、不要であれば無効にできるようにする |
