# データベーステーブルプレフィックス

## 概要

全テーブル名に変更可能なプレフィックス（デフォルト: `tp_`）を付与する仕組みを追加した。
同一 DB サーバーに複数の TightPress インスタンスを共存させたり、既存テーブルとの衝突を避けられる。

## 実装内容

| ファイル | 変更内容 |
|---|---|
| `ConnectionInterface` | `getPrefix(): string`・`execute()` メソッドを追加 |
| `AbstractDriver` | `$prefix` プロパティ・バリデーション・`getPrefix()`・`execute()` を追加 |
| 各ドライバー（SQLite/MySQL/PostgreSQL） | コンストラクタでプレフィックスを受け取って保持 |
| `Connection::create()` | プレフィックスをドライバーへ渡す |
| `QueryBuilder::table()` | `$raw` 引数追加、プレフィックス自動付与 |
| コアマイグレーション（4 クラス） | `execute()` + `getPrefix()` を使う形に変更 |
| プラグインマイグレーション（2 クラス） | `getPrefix()` を使う形に変更 |
| `PostRepository` / `PageRepository` | 生 SQL のテーブル名を `{prefix}xxx` 形式に変更 |
| `PostSeeder` / `PageSeeder` | 生 SQL のテーブル名を `{prefix}xxx` 形式に変更 |
| `.env.example` | `DB_PREFIX=tp_` を追加 |
| `config/database.php` | `'prefix'` キーを追加 |

## 使い方

### プレフィックスを変更する

`.env` の `DB_PREFIX` を変更する:

```dotenv
DB_PREFIX=mysite_   # tp_posts の代わりに mysite_posts になる
DB_PREFIX=          # プレフィックスなし（空文字）
```

使用できる文字は英数字とアンダースコアのみ。変更後は `php setup.php` でテーブルを再作成する。

### QueryBuilder からの利用

```php
// PostRepository などは論理名を渡すだけでプレフィックスが自動付与される
$this->connection->table('posts')->where('status', 'publish')->get();
// → SELECT * FROM tp_posts WHERE status = ?
```

### マイグレーションでの利用

```php
$prefix = $this->connection->getPrefix();
$this->connection->execute("CREATE TABLE IF NOT EXISTS {$prefix}options (...)");
```

## 技術的な補足

- プレフィックスは `AbstractDriver` が `protected string $prefix` として保持する。
- `Connection::create()` がドライバーコンストラクタに渡す config 配列に `prefix` キーを追加する。
- プレフィックスを後から変更すると既存データが参照できなくなるため、初回インストール時に決定して以後変更しないこと。
- `QueryBuilder::table($name, raw: true)` を指定するとプレフィックスを付与しない（JOIN 先テーブル等に使用）。
