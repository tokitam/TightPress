# [Phase 1] 1-10. シードデータ

## 概要

Phase 1 の動作確認用サンプルデータを DB に投入するシードスクリプトを実装した。
マイグレーション後に1コマンドでテストデータを準備でき、2回以上実行しても重複 INSERT しない冪等な設計になっている。

## ファイル構成

```
database/
└── seeds/
    ├── seed.php           # エントリポイント（全シーダーを順に実行）
    ├── PostSeeder.php     # 記事サンプルデータ投入（3件）
    └── PageSeeder.php     # 固定ページサンプルデータ投入（2件）
```

## 使い方

マイグレーション実行後に以下を実行する。

```bash
php database/seeds/seed.php
```

実行例:

```
Seeding posts...
  + 投入: hello-world
  + 投入: second-post
  + 投入: third-post
Seeding pages...
  + 投入: about
  + 投入: contact
Done.
```

2回目以降はスキップメッセージが表示される。

```
Seeding posts...
  - スキップ: hello-world （既に存在）
  - スキップ: second-post （既に存在）
  - スキップ: third-post （既に存在）
Seeding pages...
  - スキップ: about （既に存在）
  - スキップ: contact （既に存在）
Done.
```

## 投入データ

### posts テーブル（3件）

| slug | title | status |
|---|---|---|
| `hello-world` | Hello World | publish |
| `second-post` | 2番目の記事 | publish |
| `third-post` | 3番目の記事 | publish |

### pages テーブル（2件）

| slug | title | sort_order | status |
|---|---|---|---|
| `about` | このサイトについて | 1 | publish |
| `contact` | お問い合わせ | 2 | publish |

## 冪等性の仕組み

各シーダーは `slug` で重複チェックを行い、以下の手順で処理する。

1. `SELECT id FROM posts WHERE slug = ?` で既存レコードを確認する
2. 結果が空（存在しない）の場合のみ `INSERT` を実行する
3. 結果が存在する場合はスキップしてメッセージを出力する

## 技術的な補足

- `PostSeeder` と `PageSeeder` はどちらも `Application` を受け取り、DI コンテナ経由で `ConnectionInterface` を取得する設計になっている
- `author_id`・`thumbnail_id`・`template` など、シードデータで不要なカラムは `NULL` で投入する
- `comment_status` は posts テーブルのデフォルト `'open'` として固定で投入する
- namespace は `TightPress\Database\Seeds` とし、`composer.json` の `autoload-dev` に `"TightPress\\Database\\Seeds\\": "database/seeds/"` を追加することで PSR-4 オートロードを有効にしている。開発環境では `composer install`（`--no-dev` なし）で自動的に読み込まれる
