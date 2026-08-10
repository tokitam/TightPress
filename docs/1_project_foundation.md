# [Phase 1] 1-1. プロジェクト基盤

## 概要

TightPress の実行基盤となるファイル群を整備した。
Composer PSR-4 オートロードを確立し、DI コンテナを起点として全サービスが組み立てられる構造を作成した。

## 実装内容

| ファイル | 役割 |
|---|---|
| `composer.json` | PSR-4 オートロード設定・外部依存定義 |
| `config/app.php` | アプリケーション設定（名前・URL・テーマ・デバッグ・タイムゾーン） |
| `config/database.php` | DB 接続設定（SQLite / MySQL / PostgreSQL の切り替え） |
| `bootstrap.php` | DI コンテナ初期化・各サービスのバインド・プラグイン起動 |
| `index.php` | HTTP リクエストを受け取りルーターへ渡すエントリポイント |
| `src/Compat/functions.php` | WordPress 互換グローバル関数プレースホルダー |

## 使い方

1. `composer install` で依存ライブラリをインストールする
2. `php -S localhost:8080 index.php` で開発サーバーを起動する
3. ブラウザで `http://localhost:8080` にアクセスし、リクエストがルーターまで届くことを確認する

環境ごとに設定を変える場合は `config/app.php` と `config/database.php` を編集する（`.gitignore` で差し替え可能）。

## 技術的な補足

- 外部依存は `psr/event-dispatcher`（PSR-14 インターフェース）のみ。
- `Application::setInstance($app)` でグローバルヘルパー `app()` からコンテナを参照できるようにしているが、グローバル変数は使わず static プロパティに閉じ込める。
- `config/` の値は Phase 1 では環境変数サポート不要。デプロイ環境ごとのファイル差し替えで対応する。
- コアクラス群（`Application`, `Config`, `Connection` 等）は後続 issue で実装する。
