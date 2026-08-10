# 公開エリアを public/ に限定

## 概要

Web サーバーのドキュメントルートを `public/` に限定することで、
`src/` / `config/` / `vendor/` / `plugins/` などの内部ファイルへの HTTP アクセスを物理的に遮断する。

## 実装内容

| 変更 | 内容 |
|---|---|
| `index.php` → `public/index.php` | エントリポイントを public/ に移動 |
| `.htaccess` → `public/.htaccess` | Apache 設定を public/ に移動・更新 |
| `themes/` → `public/themes/` | テーマファイル（CSS/JS/PHP テンプレート）を public/ に移動 |
| `bootstrap.php` | `ROOT_DIR` / `PUBLIC_DIR` 定数を追加 |
| `config/app.php` | `themes_dir` / `uploads_dir` を `PUBLIC_DIR` 基準に変更 |
| `bootstrap.php` `ThemeLoader` 登録 | `config.app.themes_dir` を使うよう変更 |
| `docs/nginx.conf.example` | `root` を `public/` に更新 |

## 使い方

### PHP ビルトインサーバー

```bash
php -S localhost:8080 -t public public/index.php
```

### Apache

ドキュメントルートを `tightpress/public/` に向ける（`.htaccess` が自動的に URL リライトを処理）。

### nginx

`docs/nginx.conf.example` を参照。`root` を `tightpress/public/` に設定する。

## 技術的な補足

- `bootstrap.php` は `public/index.php` と `setup.php`（CLI）の両方から呼ばれるため、
  `ROOT_DIR` が未定義な場合は `__DIR__`（= プロジェクトルート）をフォールバックとして使う。
- テーマの PHP テンプレートは `public/themes/` に置かれるが、
  `TemplateEngine::render()` 経由（`require`）でのみ実行され、HTTP から直接 PHP を実行できない。
- `database/` / `vendor/` / `src/` は `public/` 外に残るため、Web から一切アクセスできない。
