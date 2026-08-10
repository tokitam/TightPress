# インストールと環境設定

## 概要

`.env` ファイルによる環境別設定と、`php setup.php` 1コマンドで新しい環境にセットアップできる仕組みを追加した。
Apache / nginx 向けの URL リライト設定も同梱する。

## 実装内容

| ファイル | 変更種別 | 内容 |
|---|---|---|
| `src/Core/Config/DotEnv.php` | 新規 | `.env` パーサー（外部ライブラリ不使用） |
| `.env.example` | 新規 | 環境設定テンプレート（リポジトリに含める） |
| `.env` | 新規・gitignore 対象 | 実際の環境設定 |
| `.gitignore` | 修正 | `.env` を追加 |
| `.htaccess` | 新規 | Apache URL リライト設定 |
| `setup.php` | 新規 | 初回セットアップ CLI スクリプト |
| `config/app.php` | 修正 | `$_ENV` から値を読む形に変更 |
| `config/database.php` | 修正 | `$_ENV` から値を読む形に変更 |
| `bootstrap.php` | 修正 | `DotEnv::load()` を冒頭に追加 |
| `docs/nginx.conf.example` | 新規 | nginx 設定例 |

## 使い方

### 初回セットアップ

```bash
# リポジトリをクローン後
cp .env.example .env
# .env を編集して DB 接続情報・サイト URL を設定

php setup.php          # テーブル作成のみ
php setup.php --seed   # テーブル作成 + サンプルデータ投入

# PHP ビルトインサーバーで起動
php -S localhost:8080 index.php
```

### 環境設定（`.env`）

```
APP_URL=http://localhost:8080
APP_THEME=twentytwentythree
DB_DRIVER=sqlite
DB_PATH=./database/tightpress.sqlite
```

MySQL/PostgreSQL を使う場合は `DB_DRIVER` を変更し、`DB_HOST`・`DB_NAME` 等を設定する。

## 技術的な補足

- `DotEnv::load()` は既存の `$_ENV` / `putenv` 済みの値を上書きしない。Docker・CI で環境変数を注入している場合でも `.env` が干渉しない。
- `.env` の `DB_PATH` に相対パスを指定した場合、`config/database.php` 内で `__DIR__` 基準の絶対パスに変換する。
- `setup.php` は PluginManager を通じて各プラグインの `install()` を呼び出すことでテーブルを作成する。
