# [Phase 1] 1-6. WordPress 互換テンプレートタグ

## 概要

twentytwentythree テーマが使用する WordPress テンプレートタグをグローバル関数として実装した。
`src/Compat/` にスタイル・スクリプトのキュー管理クラスと、全 WP 互換グローバル関数を整備した。

## 実装ファイル

| ファイル | 役割 |
|---|---|
| `src/Compat/AssetQueue.php` | スタイル・スクリプトのキュー管理クラス |
| `src/Compat/functions.php` | WP 互換グローバル関数（app() ヘルパーを含む全関数） |

## AssetQueue クラス

`TightPress\Compat\AssetQueue` クラスは `wp_enqueue_style()` / `wp_enqueue_script()` 互換のキュー機能を提供する。

### メソッド一覧

| メソッド | 説明 |
|---|---|
| `enqueueStyle($handle, $src, $deps, $ver, $media)` | スタイルをキューに追加する |
| `enqueueScript($handle, $src, $deps, $ver, $inFooter)` | スクリプトをキューに追加する |
| `dequeueStyle($handle)` | キューからスタイルを削除する |
| `dequeueScript($handle)` | キューからスクリプトを削除する |
| `printStyles()` | `<link>` タグを生成して返す |
| `printScripts($footer)` | `<script>` タグを生成して返す（$footer で head/footer を切り替え） |

## グローバル関数一覧

### app() ヘルパー

| 関数 | 説明 |
|---|---|
| `app(?string $abstract)` | DI コンテナまたはバインドされたサービスを返す |

### 翻訳関数（Phase 1 はパススルー）

| 関数 | 説明 |
|---|---|
| `__(string $text, string $domain)` | 翻訳文字列を返す |
| `_e(string $text, string $domain)` | 翻訳文字列を出力する |
| `_x(string $text, string $context, string $domain)` | コンテキスト付きで翻訳文字列を返す |
| `esc_html__(string $text, string $domain)` | HTML エスケープした翻訳文字列を返す |
| `esc_html_e(string $text, string $domain)` | HTML エスケープした翻訳文字列を出力する |
| `esc_attr__(string $text, string $domain)` | 属性用エスケープした翻訳文字列を返す |

### エスケープ関数

| 関数 | 説明 |
|---|---|
| `esc_html(string $text)` | HTML 特殊文字をエスケープして返す |
| `esc_attr(string $text)` | HTML 属性値用にエスケープして返す |
| `esc_url(string $url)` | URL を安全な形にエスケープして返す（http/https・相対パスのみ許可） |
| `esc_js(string $text)` | JavaScript 用にエスケープして返す |
| `wp_kses(string $string, array $allowed)` | 許可タグのみ残す（Phase 1 は素通し） |

### 条件分岐関数

| 関数 | 説明 |
|---|---|
| `is_home()` | ホームページか判定する |
| `is_front_page()` | フロントページか判定する（Phase 1 は is_home() と同一） |
| `is_single()` | 投稿単体ページか判定する |
| `is_page($slug)` | 固定ページか判定する（slug/ID で絞り込み可能） |
| `is_archive()` | アーカイブページか判定する（Phase 1 は常に false） |
| `is_category()` | カテゴリーアーカイブか判定する（Phase 1 は常に false） |
| `is_tag()` | タグアーカイブか判定する（Phase 1 は常に false） |
| `is_404()` | 404 ページか判定する |

### ループ制御

| 関数 | 説明 |
|---|---|
| `have_posts()` | 出力すべき投稿が残っているか返す |
| `the_post()` | ループカーソルを次の投稿に進める |
| `get_posts(array $args)` | 条件に合致する投稿の配列を返す（Phase 1 は最小限対応） |

### 投稿情報

| 関数 | 説明 |
|---|---|
| `get_the_title($id)` | 投稿タイトルを返す |
| `the_title($before, $after, $echo)` | 投稿タイトルを出力または返す |
| `get_the_content()` | 投稿コンテンツを返す |
| `the_content()` | 投稿コンテンツを出力する |
| `get_the_excerpt()` | 投稿の抜粋を返す（未設定時はコンテンツを 55 文字で切り詰める） |
| `the_excerpt()` | 投稿の抜粋を出力する |
| `get_permalink($id)` | パーマリンクを返す |
| `the_permalink()` | パーマリンクを出力する |
| `get_the_date($format)` | 投稿日付を指定フォーマットで返す |
| `the_date($format)` | 投稿日付を出力する |
| `get_the_author()` | 著者名を返す（Phase 1 は空文字） |
| `the_author()` | 著者名を出力する |
| `has_post_thumbnail()` | アイキャッチ画像が設定されているか返す |
| `get_post_thumbnail_id()` | アイキャッチ画像 ID を返す |
| `the_post_thumbnail($size)` | アイキャッチ画像を出力する（Phase 1 は仮出力） |
| `get_post_class($class)` | 投稿の CSS クラス配列を返す |
| `post_class($class)` | 投稿の class 属性を出力する |

### サイト情報

| 関数 | 説明 |
|---|---|
| `get_site_url()` | サイトの URL を返す（末尾スラッシュなし） |
| `home_url($path)` | ホーム URL にパスを付加して返す |
| `site_url($path)` | サイトの URL にパスを付加して返す |
| `get_bloginfo($show)` | サイトの基本情報を返す |
| `bloginfo($show)` | サイトの基本情報を出力する |

### テンプレート分割

| 関数 | 説明 |
|---|---|
| `get_header($name)` | ヘッダーテンプレートを読み込む |
| `get_footer($name)` | フッターテンプレートを読み込む |
| `get_sidebar($name)` | サイドバーテンプレートを読み込む |
| `get_template_part($slug, $name)` | テンプレートパーツを読み込む |
| `locate_template(array $templates)` | テンプレートファイルの絶対パスを返す |
| `_get_theme_dir()` | テーマディレクトリの絶対パスを返す（内部ヘルパー） |
| `_load_theme_file($file)` | テーマファイルを読み込む（内部ヘルパー） |

### アセット

| 関数 | 説明 |
|---|---|
| `wp_enqueue_style($handle, $src, $deps, $ver, $media)` | スタイルをキューに登録する |
| `wp_dequeue_style($handle)` | スタイルをキューから削除する |
| `wp_enqueue_script($handle, $src, $deps, $ver, $inFooter)` | スクリプトをキューに登録する |
| `wp_dequeue_script($handle)` | スクリプトをキューから削除する |
| `wp_print_styles()` | キューのスタイルを `<link>` タグとして出力する |
| `wp_print_scripts($footer)` | キューのスクリプトを `<script>` タグとして出力する |
| `get_stylesheet_uri()` | テーマの style.css の URL を返す |
| `get_theme_file_uri($file)` | テーマ内ファイルの URL を返す |
| `get_theme_file_path($file)` | テーマ内ファイルの絶対パスを返す |

### wp_head / wp_footer / body_class

| 関数 | 説明 |
|---|---|
| `wp_head()` | スタイル出力と wp_head アクションを実行する |
| `wp_footer()` | フッタースクリプト出力と wp_footer アクションを実行する |
| `body_class($class)` | `<body>` の class 属性を出力する |

### フック（互換実装）

| 関数 | 説明 |
|---|---|
| `add_action($hook, $callback, $priority, $acceptedArgs)` | アクションフックにコールバックを登録する |
| `do_action($hook, ...$args)` | アクションフックを実行する |
| `add_filter($hook, $callback, $priority, $acceptedArgs)` | フィルターフックにコールバックを登録する |
| `apply_filters($hook, $value, ...$args)` | フィルターフックを通して値を変換して返す |
| `remove_action($hook, $callback, $priority)` | アクションフックからコールバックを削除する（Phase 1 未実装） |
| `has_action($hook, $callback)` | フックにコールバックが登録されているか返す（Phase 1 は常に false） |

## 技術的な補足

- エスケープ関数はすべて `ENT_QUOTES | ENT_SUBSTITUTE` フラグと `UTF-8` エンコーディングを使用する。
- `esc_url()` は `http://`, `https://`, `//`（プロトコル相対）, `/`（ルート相対パス）のみ許可し、それ以外は `#` を返す。
- フック実装はグローバル変数（`$_tp_actions`, `$_tp_filters`）に蓄積する Phase 1 の簡易実装。
- `get_posts()` は `PostRepository` が未実装の場合に `\Throwable` を捕捉して空配列を返す安全なフォールバックを持つ。
- テーマディレクトリのパスは `dirname(__DIR__, 2) . '/themes/' . $theme` で計算する（`src/Compat/` の 2 階層上がプロジェクトルート）。
