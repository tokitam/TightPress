<?php

declare(strict_types=1);

// ======================================================================
// app() ヘルパー
// ======================================================================

/**
 * DI コンテナのインスタンスまたはバインドされたサービスを返す。
 *
 * @param string|null $abstract コンテナに登録されたクラス・インターフェース名。null の場合はコンテナ自身を返す。
 * @return mixed コンテナインスタンスまたは解決されたサービス
 */
function app(?string $abstract = null): mixed
{
    // Application のシングルトンインスタンスを取得する
    $instance = TightPress\Core\Application::getInstance();

    // abstract が指定されていない場合はコンテナ自身を返す
    return $abstract === null ? $instance : $instance->make($abstract);
}

// ======================================================================
// 翻訳関数（Phase 1 はパススルー実装）
// ======================================================================

/**
 * 翻訳された文字列を返す（Phase 1 はパススルー）。
 *
 * @param string $text   翻訳対象の文字列
 * @param string $domain テキストドメイン
 * @return string 翻訳された文字列（Phase 1 は入力をそのまま返す）
 */
function __(string $text, string $domain = 'default'): string
{
    // Phase 1 では翻訳機能を実装しないため、入力をそのまま返す
    return $text;
}

/**
 * 翻訳された文字列を出力する（Phase 1 はパススルー）。
 *
 * @param string $text   翻訳対象の文字列
 * @param string $domain テキストドメイン
 */
function _e(string $text, string $domain = 'default'): void
{
    // Phase 1 では翻訳機能を実装しないため、入力をそのまま出力する
    echo $text;
}

/**
 * コンテキスト付きで翻訳された文字列を返す（Phase 1 はパススルー）。
 *
 * @param string $text    翻訳対象の文字列
 * @param string $context 翻訳コンテキスト
 * @param string $domain  テキストドメイン
 * @return string 翻訳された文字列（Phase 1 は入力をそのまま返す）
 */
function _x(string $text, string $context, string $domain = 'default'): string
{
    // Phase 1 では翻訳機能を実装しないため、入力をそのまま返す
    return $text;
}

/**
 * HTML エスケープされた翻訳文字列を返す（Phase 1 はパススルー）。
 *
 * @param string $text   翻訳対象の文字列
 * @param string $domain テキストドメイン
 * @return string HTML エスケープされた翻訳文字列
 */
function esc_html__(string $text, string $domain = 'default'): string
{
    // esc_html() で HTML エスケープして返す
    return esc_html($text);
}

/**
 * HTML エスケープされた翻訳文字列を出力する（Phase 1 はパススルー）。
 *
 * @param string $text   翻訳対象の文字列
 * @param string $domain テキストドメイン
 */
function esc_html_e(string $text, string $domain = 'default'): void
{
    // esc_html() で HTML エスケープして出力する
    echo esc_html($text);
}

/**
 * 属性用エスケープされた翻訳文字列を返す（Phase 1 はパススルー）。
 *
 * @param string $text   翻訳対象の文字列
 * @param string $domain テキストドメイン
 * @return string 属性用エスケープされた翻訳文字列
 */
function esc_attr__(string $text, string $domain = 'default'): string
{
    // esc_attr() で属性用エスケープして返す
    return esc_attr($text);
}

// ======================================================================
// エスケープ関数
// ======================================================================

/**
 * HTML 特殊文字をエスケープして返す。
 *
 * @param string $text エスケープ対象の文字列
 * @return string HTML エスケープされた文字列
 */
function esc_html(string $text): string
{
    // ENT_SUBSTITUTE で不正な文字列を Unicode 代替文字に置換する
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * HTML 属性値用に特殊文字をエスケープして返す。
 *
 * @param string $text エスケープ対象の文字列
 * @return string 属性用にエスケープされた文字列
 */
function esc_attr(string $text): string
{
    // ENT_SUBSTITUTE で不正な文字列を Unicode 代替文字に置換する
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * URL を安全な形にエスケープして返す。
 *
 * http/https で始まるか、スラッシュで始まる相対パスのみ許可する。
 * それ以外は '#' を返す。
 *
 * @param string $url エスケープ対象の URL
 * @return string エスケープされた URL または '#'
 */
function esc_url(string $url): string
{
    // 前後の空白を除去する
    $url = trim($url);

    // http://, https://, または /（相対パス）で始まる URL のみ許可する
    if (preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, '/')) {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    // 許可されない URL は '#' を返す
    return '#';
}

/**
 * JavaScript 用に文字列をエスケープして返す。
 *
 * @param string $text エスケープ対象の文字列
 * @return string JavaScript 用にエスケープされた文字列
 */
function esc_js(string $text): string
{
    // addslashes() で JavaScript 内で安全に使える形にエスケープする
    return addslashes($text);
}

/**
 * 許可されたタグのみを残して HTML をフィルタリングする（Phase 1 は素通し）。
 *
 * @param string $string  フィルタリング対象の HTML 文字列
 * @param array  $allowed 許可するタグと属性の配列
 * @return string フィルタリングされた HTML 文字列（Phase 1 は入力をそのまま返す）
 */
function wp_kses(string $string, array $allowed): string
{
    // Phase 1 では素通し（後で実装）
    return $string;
}

// ======================================================================
// 条件分岐関数
// ======================================================================

/**
 * 現在のページがホームページ（投稿一覧ページ）かどうかを返す。
 *
 * @return bool ホームページの場合は true
 */
function is_home(): bool
{
    // TemplateContext から現在のページタイプを取得して判定する
    return app(TightPress\Core\Template\TemplateContext::class)->pageType === TightPress\Core\Template\PageType::Home;
}

/**
 * 現在のページがフロントページかどうかを返す。
 *
 * Phase 1 では is_home() と同一の動作をする。
 *
 * @return bool フロントページの場合は true
 */
function is_front_page(): bool
{
    return is_home();
}

/**
 * 現在のページが投稿単体ページかどうかを返す。
 *
 * @return bool 投稿単体ページの場合は true
 */
function is_single(): bool
{
    // TemplateContext から現在のページタイプを取得して判定する
    return app(TightPress\Core\Template\TemplateContext::class)->pageType === TightPress\Core\Template\PageType::Single;
}

/**
 * 現在のページが固定ページかどうかを返す。
 *
 * @param string|int $slug スラッグまたは ID を指定して特定の固定ページか確認できる。省略すると固定ページ全般を判定する。
 * @return bool 固定ページの場合は true
 */
function is_page(string|int $slug = ''): bool
{
    $ctx = app(TightPress\Core\Template\TemplateContext::class);

    // ページタイプが固定ページでない場合は false を返す
    if ($ctx->pageType !== TightPress\Core\Template\PageType::Page) {
        return false;
    }

    // slug または ID が指定されていない場合は固定ページ全般として true を返す
    if ($slug === '' || $slug === 0) {
        return true;
    }

    // スラッグまたは ID が一致するか確認する
    return $ctx->currentPost?->slug === (string) $slug || $ctx->currentPost?->id === (int) $slug;
}

/**
 * 現在のページがアーカイブページかどうかを返す（Phase 1 未対応）。
 *
 * @return bool アーカイブページの場合は true（Phase 1 では常に false）
 */
function is_archive(): bool
{
    // Phase 1 未対応のため常に false を返す
    return false;
}

/**
 * 現在のページがカテゴリーアーカイブページかどうかを返す（Phase 1 未対応）。
 *
 * @return bool カテゴリーアーカイブページの場合は true（Phase 1 では常に false）
 */
function is_category(): bool
{
    // Phase 1 未対応のため常に false を返す
    return false;
}

/**
 * 現在のページがタグアーカイブページかどうかを返す（Phase 1 未対応）。
 *
 * @return bool タグアーカイブページの場合は true（Phase 1 では常に false）
 */
function is_tag(): bool
{
    // Phase 1 未対応のため常に false を返す
    return false;
}

/**
 * 現在のページが 404 エラーページかどうかを返す。
 *
 * @return bool 404 ページの場合は true
 */
function is_404(): bool
{
    // TemplateContext から現在のページタイプを取得して判定する
    return app(TightPress\Core\Template\TemplateContext::class)->pageType === TightPress\Core\Template\PageType::NotFound;
}

// ======================================================================
// ループ制御
// ======================================================================

/**
 * ループ内に出力すべき投稿が残っているかどうかを返す。
 *
 * @return bool 投稿が残っている場合は true
 */
function have_posts(): bool
{
    // TemplateContext の hasPosts() に委譲する
    return app(TightPress\Core\Template\TemplateContext::class)->hasPosts();
}

/**
 * ループカーソルを次の投稿に進め、グローバルな現在投稿を設定する。
 */
function the_post(): void
{
    // TemplateContext の advanceCursor() に委譲する
    app(TightPress\Core\Template\TemplateContext::class)->advanceCursor();
}

/**
 * 条件に合致する投稿の配列を返す。
 *
 * Phase 1 では最小限の引数のみ対応する（numberposts, post_status, orderby, order）。
 * PostRepository に委譲し、クラスが未実装の場合は空配列を返す。
 *
 * @param array $args 取得条件の配列
 * @return array 投稿オブジェクトの配列
 */
function get_posts(array $args = []): array
{
    // numberposts が指定されていない場合は 10 件をデフォルトとする
    $limit  = (int) ($args['numberposts'] ?? 10);
    // post_status が指定されていない場合は 'publish' を使用する
    $status = $args['post_status'] ?? 'publish';

    try {
        // PostRepository に委譲して公開済み投稿を取得する
        $repo = app(TightPress\Plugin\BlogPosts\Model\PostRepository::class);
        return $repo->findPublished($limit);
    } catch (\Throwable) {
        // PostRepository が未実装の場合は空配列を返す
        return [];
    }
}

// ======================================================================
// 投稿情報
// ======================================================================

/**
 * 投稿のタイトルを返す。
 *
 * @param int $id 投稿 ID（0 の場合は現在の投稿のタイトルを返す）
 * @return string 投稿タイトル
 */
function get_the_title(int $id = 0): string
{
    $ctx = app(TightPress\Core\Template\TemplateContext::class);

    // ID が指定されていない場合は現在の投稿を使用する
    $post = $id > 0 ? null : $ctx->currentPost;

    return $post?->title ?? '';
}

/**
 * 投稿のタイトルを出力または返す。
 *
 * @param string $before タイトルの前に付加する文字列
 * @param string $after  タイトルの後に付加する文字列
 * @param bool   $echo   true の場合は出力する、false の場合は文字列を返す
 * @return string|void $echo が false の場合はタイトル文字列を返す
 */
function the_title(string $before = '', string $after = '', bool $echo = true): string
{
    // タイトルをエスケープして前後の文字列と結合する
    $title = $before . esc_html(get_the_title()) . $after;

    if ($echo) {
        // echo が true の場合は出力する
        echo $title;
    }

    return $title;
}

/**
 * 現在の投稿のコンテンツを返す。
 *
 * @return string 投稿コンテンツ
 */
function get_the_content(): string
{
    $ctx = app(TightPress\Core\Template\TemplateContext::class);

    return $ctx->currentPost?->content ?? '';
}

/**
 * 現在の投稿のコンテンツを出力する。
 */
function the_content(): void
{
    echo get_the_content();
}

/**
 * 現在の投稿の抜粋を返す。
 *
 * 抜粋が設定されていない場合は、コンテンツを 55 文字で切り詰めて返す。
 *
 * @return string 投稿の抜粋
 */
function get_the_excerpt(): string
{
    $ctx  = app(TightPress\Core\Template\TemplateContext::class);
    $post = $ctx->currentPost;

    // 現在の投稿が存在しない場合は空文字を返す
    if ($post === null) {
        return '';
    }

    $excerpt = $post->excerpt ?? '';

    // 抜粋が設定されている場合はそのまま返す
    if ($excerpt !== '') {
        return $excerpt;
    }

    // 抜粋が空の場合はコンテンツを 55 文字で切り詰めて返す
    return mb_strimwidth(strip_tags($post->content ?? ''), 0, 55, '...');
}

/**
 * 現在の投稿の抜粋を出力する。
 */
function the_excerpt(): void
{
    echo get_the_excerpt();
}

/**
 * 投稿のパーマリンクを返す。
 *
 * @param int $id 投稿 ID（0 の場合は現在の投稿のパーマリンクを返す）
 * @return string 投稿のパーマリンク URL
 */
function get_permalink(int $id = 0): string
{
    $ctx  = app(TightPress\Core\Template\TemplateContext::class);

    // ID が指定されていない場合は現在の投稿を使用する
    $post = $id > 0 ? null : $ctx->currentPost;

    // 投稿が存在しない場合はホーム URL を返す
    if ($post === null) {
        return home_url('/');
    }

    // comment_status プロパティの有無でポストとページを区別する
    if (isset($post->content) && property_exists($post, 'comment_status')) {
        // 投稿（post）の場合は /posts/{slug} のパスを使用する
        return home_url('/posts/' . $post->slug);
    }

    // 固定ページの場合は /{slug} のパスを使用する
    return home_url('/' . $post->slug);
}

/**
 * 現在の投稿のパーマリンクをエスケープして出力する。
 */
function the_permalink(): void
{
    echo esc_url(get_permalink());
}

/**
 * 現在の投稿の日付を指定フォーマットで返す。
 *
 * @param string $format date() 関数が受け付けるフォーマット文字列
 * @return string フォーマットされた日付文字列
 */
function get_the_date(string $format = 'Y-m-d'): string
{
    $ctx  = app(TightPress\Core\Template\TemplateContext::class);
    $date = $ctx->currentPost?->publishedAt;

    // 日付が設定されていない場合は空文字を返す
    if ($date === null) {
        return '';
    }

    // DateTimeImmutable の場合は format() メソッドを使用する
    return $date instanceof \DateTimeImmutable ? $date->format($format) : (string) $date;
}

/**
 * 現在の投稿の日付をエスケープして出力する。
 *
 * @param string $format date() 関数が受け付けるフォーマット文字列
 */
function the_date(string $format = 'Y-m-d'): void
{
    echo esc_html(get_the_date($format));
}

/**
 * 現在の投稿の著者名を返す（Phase 1 は未実装）。
 *
 * @return string 著者名（Phase 1 では常に空文字）
 */
function get_the_author(): string
{
    // Phase 1 では著者情報が未実装のため空文字を返す
    return '';
}

/**
 * 現在の投稿の著者名を出力する（Phase 1 は未実装）。
 */
function the_author(): void
{
    echo esc_html(get_the_author());
}

/**
 * 現在の投稿にアイキャッチ画像が設定されているかどうかを返す。
 *
 * @return bool アイキャッチ画像が設定されている場合は true
 */
function has_post_thumbnail(): bool
{
    $ctx = app(TightPress\Core\Template\TemplateContext::class);

    // thumbnailId が設定されているか確認する
    return ($ctx->currentPost?->thumbnailId ?? null) !== null;
}

/**
 * 現在の投稿のアイキャッチ画像 ID を返す。
 *
 * @return int|null アイキャッチ画像の ID、設定されていない場合は null
 */
function get_post_thumbnail_id(): int|null
{
    return app(TightPress\Core\Template\TemplateContext::class)->currentPost?->thumbnailId ?? null;
}

/**
 * 現在の投稿のアイキャッチ画像を出力する（Phase 1 は仮出力）。
 *
 * @param string $size 画像サイズ（Phase 1 では無視する）
 */
function the_post_thumbnail(string $size = 'post-thumbnail'): void
{
    $id = get_post_thumbnail_id();

    // アイキャッチ画像が設定されていない場合は何も出力しない
    if ($id === null) {
        return;
    }

    // Phase 1 では仮の img タグを出力する（メディア機能は未実装）
    echo '<img src="" alt="" />';
}

/**
 * 投稿に適用する CSS クラスの配列を返す。
 *
 * @param string|array $class 追加する CSS クラス
 * @return array CSS クラスの配列
 */
function get_post_class(string|array $class = ''): array
{
    // 基本クラスとして 'post' を設定する
    $classes = ['post'];

    // 追加クラスが指定されている場合はマージする
    if ($class) {
        $classes = array_merge($classes, (array) $class);
    }

    // 投稿単体ページの場合は 'single' クラスを追加する
    if (is_single()) {
        $classes[] = 'single';
    }

    return $classes;
}

/**
 * 投稿の CSS クラスを class 属性として出力する。
 *
 * @param string|array $class 追加する CSS クラス
 */
function post_class(string|array $class = ''): void
{
    echo 'class="' . implode(' ', get_post_class($class)) . '"';
}

// ======================================================================
// サイト情報
// ======================================================================

/**
 * サイトの URL を返す（末尾スラッシュなし）。
 *
 * @return string サイトの URL
 */
function get_site_url(): string
{
    try {
        $cfg = app(TightPress\Core\Config\Config::class);

        // 末尾スラッシュを除去して返す
        return rtrim($cfg->get('app.url'), '/');
    } catch (\Throwable) {
        // Config が未実装の場合はデフォルト値を返す
        return 'http://localhost:8080';
    }
}

/**
 * ホーム URL にパスを付加して返す。
 *
 * @param string $path 付加するパス（先頭スラッシュは除去される）
 * @return string ホーム URL
 */
function home_url(string $path = ''): string
{
    // 先頭スラッシュを除去してパスを結合する
    return get_site_url() . '/' . ltrim($path, '/');
}

/**
 * サイトの URL にパスを付加して返す。
 *
 * @param string $path 付加するパス
 * @return string サイトの URL
 */
function site_url(string $path = ''): string
{
    return home_url($path);
}

/**
 * サイトの基本情報を返す。
 *
 * @param string $show 取得する情報の種類（'name', 'url', 'home', 'charset', 'language'）
 * @return string サイト情報の文字列
 */
function get_bloginfo(string $show = ''): string
{
    try {
        $cfg = app(TightPress\Core\Config\Config::class);

        // $show の値に応じて対応する設定値を返す
        return match ($show) {
            'name'        => $cfg->get('app.name') ?? 'TightPress',
            'url', 'home' => get_site_url(),
            'charset'     => 'UTF-8',
            'language'    => 'ja',
            default       => '',
        };
    } catch (\Throwable) {
        // Config が未実装の場合は空文字を返す
        return '';
    }
}

/**
 * サイトの基本情報をエスケープして出力する。
 *
 * @param string $show 取得する情報の種類
 */
function bloginfo(string $show = ''): void
{
    echo esc_html(get_bloginfo($show));
}

// ======================================================================
// テンプレート分割
// ======================================================================

/**
 * ヘッダーテンプレートを読み込んで出力する。
 *
 * @param string|null $name カスタムヘッダー名（例: 'custom' の場合 header-custom.php を読み込む）
 */
function get_header(?string $name = null): void
{
    // 名前が指定されている場合はカスタムヘッダーファイルを、そうでない場合は header.php を使用する
    $file = $name ? "header-{$name}.php" : 'header.php';
    _load_theme_file($file);
}

/**
 * フッターテンプレートを読み込んで出力する。
 *
 * @param string|null $name カスタムフッター名（例: 'custom' の場合 footer-custom.php を読み込む）
 */
function get_footer(?string $name = null): void
{
    // 名前が指定されている場合はカスタムフッターファイルを、そうでない場合は footer.php を使用する
    $file = $name ? "footer-{$name}.php" : 'footer.php';
    _load_theme_file($file);
}

/**
 * サイドバーテンプレートを読み込んで出力する。
 *
 * @param string|null $name カスタムサイドバー名（例: 'custom' の場合 sidebar-custom.php を読み込む）
 */
function get_sidebar(?string $name = null): void
{
    // 名前が指定されている場合はカスタムサイドバーファイルを、そうでない場合は sidebar.php を使用する
    $file = $name ? "sidebar-{$name}.php" : 'sidebar.php';
    _load_theme_file($file);
}

/**
 * テンプレートパーツを読み込んで出力する。
 *
 * @param string      $slug テンプレートのベーススラッグ
 * @param string|null $name バリエーション名（例: 'header' の場合 {slug}-header.php を先に探す）
 */
function get_template_part(string $slug, ?string $name = null): void
{
    $files = [];

    // バリエーション名が指定されている場合は先に試みる
    if ($name !== null) {
        $files[] = "{$slug}-{$name}.php";
    }

    // ベースファイルをフォールバックとして追加する
    $files[] = "{$slug}.php";

    // ファイルを順番に探して最初に見つかったものを読み込む
    foreach ($files as $file) {
        $path = locate_template([$file]);
        if ($path !== '') {
            require $path;
            return;
        }
    }
}

/**
 * テンプレートファイルの絶対パスを探して返す。
 *
 * @param array $templates 探すテンプレートファイル名の配列
 * @return string 見つかったテンプレートの絶対パス（見つからない場合は空文字）
 */
function locate_template(array $templates): string
{
    $themeDir = _get_theme_dir();

    foreach ($templates as $file) {
        // 空のファイル名はスキップする
        if ($file === '') {
            continue;
        }

        $path = $themeDir . '/' . $file;

        // ファイルが存在すれば絶対パスを返す
        if (file_exists($path)) {
            return $path;
        }
    }

    // 見つからなかった場合は空文字を返す
    return '';
}

/**
 * テーマディレクトリの絶対パスを返す内部ヘルパー。
 *
 * @return string テーマディレクトリの絶対パス
 */
function _get_theme_dir(): string
{
    try {
        $cfg   = app(TightPress\Core\Config\Config::class);
        // 設定からテーマ名を取得する。未設定の場合はデフォルトテーマを使用する
        $theme = $cfg->get('app.theme') ?? 'twentytwentythree';
    } catch (\Throwable) {
        // Config が未実装の場合はデフォルトテーマを使用する
        $theme = 'twentytwentythree';
    }

    // src/ の 2 つ上のディレクトリ（プロジェクトルート）配下の themes/ ディレクトリを使用する
    return dirname(__DIR__, 2) . '/themes/' . $theme;
}

/**
 * テーマファイルを読み込む内部ヘルパー。
 *
 * @param string $file 読み込むファイル名（テーマディレクトリからの相対パス）
 */
function _load_theme_file(string $file): void
{
    $path = _get_theme_dir() . '/' . $file;

    // ファイルが存在する場合のみ読み込む
    if (file_exists($path)) {
        require $path;
    }
}

// ======================================================================
// アセット（スタイル・スクリプト）
// ======================================================================

/**
 * スタイルシートをキューに登録する。
 *
 * @param string        $handle スタイルの識別ハンドル名
 * @param string        $src    スタイルシートの URL
 * @param list<string>  $deps   依存するハンドル名のリスト
 * @param ?string       $ver    バージョン文字列
 * @param string        $media  メディアタイプ（デフォルト: 'all'）
 */
function wp_enqueue_style(
    string $handle,
    string $src = '',
    array $deps = [],
    ?string $ver = null,
    string $media = 'all'
): void {
    app(TightPress\Compat\AssetQueue::class)->enqueueStyle($handle, $src, $deps, $ver, $media);
}

/**
 * キューに登録されたスタイルシートを削除する。
 *
 * @param string $handle 削除対象のハンドル名
 */
function wp_dequeue_style(string $handle): void
{
    app(TightPress\Compat\AssetQueue::class)->dequeueStyle($handle);
}

/**
 * スクリプトをキューに登録する。
 *
 * @param string        $handle   スクリプトの識別ハンドル名
 * @param string        $src      スクリプトの URL
 * @param list<string>  $deps     依存するハンドル名のリスト
 * @param ?string       $ver      バージョン文字列
 * @param bool          $inFooter true の場合はフッターに出力する
 */
function wp_enqueue_script(
    string $handle,
    string $src = '',
    array $deps = [],
    ?string $ver = null,
    bool $inFooter = false
): void {
    app(TightPress\Compat\AssetQueue::class)->enqueueScript($handle, $src, $deps, $ver, $inFooter);
}

/**
 * キューに登録されたスクリプトを削除する。
 *
 * @param string $handle 削除対象のハンドル名
 */
function wp_dequeue_script(string $handle): void
{
    app(TightPress\Compat\AssetQueue::class)->dequeueScript($handle);
}

/**
 * キューに登録されたスタイルシートを <link> タグとして出力する。
 */
function wp_print_styles(): void
{
    echo app(TightPress\Compat\AssetQueue::class)->printStyles();
}

/**
 * キューに登録されたスクリプトを <script> タグとして出力する。
 *
 * @param bool $footer true の場合はフッター用スクリプトを出力する
 */
function wp_print_scripts(bool $footer = false): void
{
    echo app(TightPress\Compat\AssetQueue::class)->printScripts($footer);
}

/**
 * 現在のテーマのスタイルシート（style.css）の URL を返す。
 *
 * @return string style.css の URL
 */
function get_stylesheet_uri(): string
{
    return get_theme_file_uri('style.css');
}

/**
 * テーマ内のファイルの URL を返す。
 *
 * @param string $file テーマディレクトリからの相対ファイルパス
 * @return string ファイルの URL
 */
function get_theme_file_uri(string $file = ''): string
{
    try {
        $cfg   = app(TightPress\Core\Config\Config::class);
        // 設定からテーマ名とベース URL を取得する
        $theme = $cfg->get('app.theme') ?? 'twentytwentythree';
        $base  = rtrim($cfg->get('app.url'), '/');
    } catch (\Throwable) {
        // Config が未実装の場合はデフォルト値を使用する
        $theme = 'twentytwentythree';
        $base  = 'http://localhost:8080';
    }

    // テーマディレクトリへの URL にファイルパスを付加して返す
    return $base . '/themes/' . $theme . ($file !== '' ? '/' . $file : '');
}

/**
 * テーマ内のファイルの絶対パスを返す。
 *
 * @param string $file テーマディレクトリからの相対ファイルパス
 * @return string ファイルの絶対パス
 */
function get_theme_file_path(string $file = ''): string
{
    return _get_theme_dir() . ($file !== '' ? '/' . $file : '');
}

// ======================================================================
// wp_head / wp_footer / body_class
// ======================================================================

/**
 * <head> 内に必要なスタイルや wp_head アクションを実行する。
 *
 * スタイルシートを出力し、wp_head アクションフックを発火する。
 */
function wp_head(): void
{
    // キューに登録されたスタイルを出力する
    wp_print_styles();

    // wp_head アクションを発火する（プラグインなどが追加できる）
    do_action('wp_head');
}

/**
 * </body> 前に必要なスクリプトや wp_footer アクションを実行する。
 *
 * フッター用スクリプトを出力し、wp_footer アクションフックを発火する。
 */
function wp_footer(): void
{
    // フッター用スクリプトを出力する
    wp_print_scripts(true);

    // wp_footer アクションを発火する（プラグインなどが追加できる）
    do_action('wp_footer');
}

/**
 * <body> タグの CSS クラスを class 属性として出力する。
 *
 * @param string|array $class 追加する CSS クラス
 */
function body_class(string|array $class = ''): void
{
    // デフォルトのクラスはホームページ用
    $classes = ['home'];

    // ページタイプに応じてクラスを設定する
    if (is_single()) {
        $classes = ['single', 'single-post'];
    }
    if (is_page()) {
        $classes = ['page'];
    }
    if (is_404()) {
        $classes = ['error404'];
    }

    // 追加クラスが指定されている場合はマージする
    if ($class) {
        $classes = array_merge($classes, (array) $class);
    }

    echo 'class="' . esc_attr(implode(' ', $classes)) . '"';
}

// ======================================================================
// フック（互換実装）
// ======================================================================

/**
 * アクションフックにコールバックを登録する。
 *
 * @param string   $hook         フック名
 * @param callable $callback     実行するコールバック関数
 * @param int      $priority     実行優先度（数値が小さいほど先に実行される）
 * @param int      $acceptedArgs コールバックが受け取る引数の数
 */
function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
{
    // グローバル変数にアクションのコールバックを蓄積する
    global $_tp_actions;
    $_tp_actions[$hook][$priority][] = $callback;
}

/**
 * 指定されたアクションフックに登録されたコールバックをすべて実行する。
 *
 * @param string $hook   フック名
 * @param mixed  ...$args コールバックに渡す引数
 */
function do_action(string $hook, mixed ...$args): void
{
    global $_tp_actions;

    // フックにコールバックが登録されていない場合は何もしない
    if (empty($_tp_actions[$hook])) {
        return;
    }

    // 優先度順にコールバックを実行する
    ksort($_tp_actions[$hook]);
    foreach ($_tp_actions[$hook] as $callbacks) {
        foreach ($callbacks as $cb) {
            $cb(...$args);
        }
    }
}

/**
 * フィルターフックにコールバックを登録する。
 *
 * @param string   $hook         フック名
 * @param callable $callback     実行するコールバック関数
 * @param int      $priority     実行優先度（数値が小さいほど先に実行される）
 * @param int      $acceptedArgs コールバックが受け取る引数の数
 */
function add_filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
{
    // グローバル変数にフィルターのコールバックを蓄積する
    global $_tp_filters;
    $_tp_filters[$hook][$priority][] = $callback;
}

/**
 * 指定されたフィルターフックに登録されたコールバックを通して値を変換して返す。
 *
 * @param string $hook    フック名
 * @param mixed  $value   変換対象の値
 * @param mixed  ...$args コールバックに渡す追加引数
 * @return mixed フィルタリングされた値
 */
function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
{
    global $_tp_filters;

    // フックにコールバックが登録されていない場合は元の値をそのまま返す
    if (empty($_tp_filters[$hook])) {
        return $value;
    }

    // 優先度順にコールバックを適用して値を変換していく
    ksort($_tp_filters[$hook]);
    foreach ($_tp_filters[$hook] as $callbacks) {
        foreach ($callbacks as $cb) {
            $value = $cb($value, ...$args);
        }
    }

    return $value;
}

/**
 * アクションフックからコールバックを削除する（Phase 1 では未実装）。
 *
 * @param string   $hook     フック名
 * @param callable $callback 削除対象のコールバック関数
 * @param int      $priority コールバックの優先度
 */
function remove_action(string $hook, callable $callback, int $priority = 10): void
{
    // Phase 1 では未実装
}

/**
 * 指定されたフックにコールバックが登録されているかどうかを返す（Phase 1 では未実装）。
 *
 * @param string        $hook     フック名
 * @param callable|bool $callback チェック対象のコールバック（false の場合は登録の有無のみ確認）
 * @return bool コールバックが登録されている場合は true（Phase 1 では常に false）
 */
function has_action(string $hook, callable|bool $callback = false): bool
{
    // Phase 1 では常に false を返す
    return false;
}
