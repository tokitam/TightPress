# テーマの style.css を読み込んでデザインを反映させる

## 概要

`public/themes/twentytwentythree/style.css` をブラウザに読み込み、テーマのデザインが反映されるようにした。

## 実装内容

### 1. テンプレートへの `<link>` タグ追加

`index.php` / `single.php` / `page.php` の `<head>` 内に追加:

```php
<link rel="stylesheet" href="<?php echo esc_url(get_stylesheet_uri()); ?>">
```

`get_stylesheet_uri()` は `get_theme_file_uri('style.css')` に委譲し、
`http://<APP_URL>/themes/<theme>/style.css` を返す。

### 2. `_get_theme_dir()` のパス修正

`src/Compat/functions.php` の内部ヘルパーが `themes/` (旧パス) を参照していた問題を修正。
優先順位:

1. `app.themes_dir` 設定値（`PUBLIC_DIR . '/themes'`）
2. `PUBLIC_DIR` 定数
3. フォールバック: `プロジェクトルート/public/themes`

### 3. `AssetQueue` のシングルトン登録

`bootstrap.php` に追加:

```php
$app->singleton(AssetQueue::class, fn() => new AssetQueue());
```

`wp_enqueue_style()` / `wp_head()` が同一インスタンスを共有するために必要。

### 4. PHP 組み込みサーバー用の静的ファイル素通し

`public/index.php` に追加:

```php
if (PHP_SAPI === 'cli-server') {
    $staticFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($staticFile)) {
        return false;
    }
}
```

`php -S` は `.htaccess` を読まないため、存在するファイルはルーターをバイパスして直接配信する。
Apache/Nginx 環境では `.htaccess` / `nginx.conf` の設定が有効になるため、この分岐は無視される。

## 使い方

```bash
php -S localhost:8080 -t public public/index.php
```

ブラウザで `http://localhost:8080/` を開き、DevTools の Network タブで
`style.css` が 200 で返ることを確認する。

## 技術的な補足

- `get_stylesheet_uri()` / `get_theme_file_uri()` は `src/Compat/functions.php` に実装済み
- URL は `app.url` + `/themes/` + `app.theme` + `/style.css` で構成される
- `.htaccess` の `RewriteRule ^(uploads|themes)/ - [L]` は Apache 環境で同等の素通し処理を行う
