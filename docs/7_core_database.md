# 7. コアデータベース層・DIコンテナ・設定管理

## 概要

TightPress のコア基盤となるデータベース層、DI コンテナ（`Application`）、設定管理（`Config`）の実装ドキュメント。

SQLite / MySQL / PostgreSQL の3ドライバーに対応し、プリペアドステートメントによる安全なクエリ実行と、メソッドチェーンによる直感的なクエリ構築を提供する。

---

## ディレクトリ構成

```
src/Core/
├── Application.php                          # DIコンテナ
├── Config/
│   └── Config.php                           # ドット記法設定管理
└── Database/
    ├── ConnectionInterface.php              # DB接続の共通インターフェース
    ├── Connection.php                       # ドライバー生成ファクトリ
    ├── QueryBuilder.php                     # クエリビルダー
    ├── Drivers/
    │   ├── AbstractDriver.php               # ドライバー共通処理
    │   ├── SQLiteDriver.php
    │   ├── MySQLDriver.php
    │   └── PostgreSQLDriver.php
    └── Migration/
        ├── MigrationInterface.php
        ├── Migrator.php
        └── Migrations/
            ├── CreateOptionsTable.php
            ├── CreateUsersTable.php
            ├── CreateTaxonomiesTable.php
            └── CreateTermsTable.php
```

---

## Application（DIコンテナ）

`src/Core/Application.php`

### 役割

- サービスのバインドと解決を担うシンプルな DI コンテナ
- `bind()` で毎回新規生成、`singleton()` で初回のみ生成してキャッシュ
- `app()` グローバルヘルパー経由でどこからでも参照できる

### 主要メソッド

| メソッド | 説明 |
|---|---|
| `bind(string $abstract, callable $factory)` | サービスをバインドする（呼び出しのたびに新規生成） |
| `singleton(string $abstract, callable $factory)` | シングルトンとしてバインドする（初回のみ生成） |
| `make(string $abstract)` | サービスを解決して返す |
| `static setInstance(self $app)` | グローバルインスタンスを登録する |
| `static getInstance()` | グローバルインスタンスを返す |

### 使用例

```php
$app = new Application();

// シングルトン登録
$app->singleton(Config::class, fn() => new Config([...]));

// 解決
$config = $app->make(Config::class);

// グローバル登録
Application::setInstance($app);
```

---

## Config（設定管理）

`src/Core/Config/Config.php`

### 役割

ネストした連想配列をドット記法で参照できるラッパークラス。

### 使用例

```php
$config = new Config([
    'database' => [
        'driver' => 'sqlite',
        'sqlite' => ['path' => '/path/to/db.sqlite'],
    ],
]);

$config->get('database.driver');          // 'sqlite'
$config->get('database.sqlite.path');     // '/path/to/db.sqlite'
$config->get('database.mysql.host', '127.0.0.1'); // デフォルト値
$config->has('database.driver');          // true
```

---

## データベース層

### ConnectionInterface

`src/Core/Database/ConnectionInterface.php`

全ドライバーが実装する共通インターフェース。

| メソッド | 戻り値 | 説明 |
|---|---|---|
| `select(string $sql, array $bindings)` | `array` | SELECT 実行・全行返却 |
| `insert(string $sql, array $bindings)` | `int` | INSERT 実行・挿入 ID 返却 |
| `update(string $sql, array $bindings)` | `int` | UPDATE 実行・影響行数返却 |
| `delete(string $sql, array $bindings)` | `int` | DELETE 実行・影響行数返却 |
| `transaction(callable $callback)` | `mixed` | トランザクション内でコールバックを実行 |
| `getDriverName()` | `string` | ドライバー名返却（'sqlite' / 'mysql' / 'pgsql'） |
| `getPdo()` | `\PDO` | 生 PDO を返却（DDL 直接実行用） |

### Connection（ファクトリ）

`src/Core/Database/Connection.php`

設定配列の `driver` キーに基づいてドライバーインスタンスを生成する静的ファクトリ。

```php
$connection = Connection::create([
    'driver' => 'sqlite',
    'sqlite' => ['path' => '/var/db/tightpress.sqlite'],
]);
```

### ドライバー詳細

#### SQLiteDriver

- WAL モードを有効化（`PRAGMA journal_mode=WAL;`）してマルチプロセス書き込みに対応
- タイムアウト: `PDO::ATTR_TIMEOUT => 5`
- DSN: `sqlite:/path/to/file.db`

#### MySQLDriver

- DSN: `mysql:host=HOST;port=PORT;dbname=DBNAME;charset=CHARSET`
- エミュレートプリペアドを無効化（`ATTR_EMULATE_PREPARES => false`）して型安全に

#### PostgreSQLDriver

- DSN: `pgsql:host=HOST;port=PORT;dbname=DBNAME`
- INSERT の ID 取得に `RETURNING id` 句を使用（`lastInsertId()` は使用しない）

### QueryBuilder

`src/Core/Database/QueryBuilder.php`

メソッドチェーンで SQL クエリを組み立てる。各メソッドは `clone` を返すためイミュータブルに使える。

```php
$qb = new QueryBuilder($connection);

// SELECT
$rows = $qb->table('options')
    ->select('name', 'value')
    ->where('autoload', 1)
    ->orderBy('name')
    ->limit(10)
    ->get();

// INSERT
$id = $qb->table('options')->insert([
    'name'       => 'siteurl',
    'value'      => 'https://example.com',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);

// UPDATE
$affected = $qb->table('options')
    ->where('name', 'siteurl')
    ->update(['value' => 'https://new.example.com', 'updated_at' => date('Y-m-d H:i:s')]);

// DELETE
$affected = $qb->table('options')
    ->where('name', 'old_option')
    ->delete();
```

Phase 1 では WHERE は AND 結合のみ対応。OR / LIKE / サブクエリは将来の課題。

---

## マイグレーション

### Migrator

`src/Core/Database/Migration/Migrator.php`

登録されたマイグレーションを管理し、`run()` で全 up()、`rollback()` で逆順に全 down() を実行する。

Phase 1 では実行済みチェック（migrations テーブル）は行わずシンプルに全実行とする。

```php
$migrator = new Migrator($connection);
$migrator->add(new CreateOptionsTable($connection));
$migrator->add(new CreateUsersTable($connection));
$migrator->add(new CreateTaxonomiesTable($connection));
$migrator->add(new CreateTermsTable($connection));

$migrator->run();      // 全テーブル作成
$migrator->rollback(); // 全テーブル削除（逆順）
```

### マイグレーションクラス一覧

| クラス | 作成テーブル | 備考 |
|---|---|---|
| `CreateOptionsTable` | `options` | サイト設定（get_option 相当） |
| `CreateUsersTable` | `users` | ユーザーアカウント |
| `CreateTaxonomiesTable` | `taxonomies` | タクソノミー定義 |
| `CreateTermsTable` | `terms` | タクソノミーの各ターム（インデックスあり） |

---

## 設計上の注意点

- **SQLite 互換のインデックス**: SQLite は `CREATE TABLE` 内に `INDEX` を書けないため、`CreateTermsTable` では `CREATE INDEX IF NOT EXISTS` を別途実行している
- **PostgreSQL の INSERT ID**: `lastInsertId()` はシーケンス名の指定が必要なため、`RETURNING id` 句で取得する方式を採用
- **イミュータブルな QueryBuilder**: 各メソッドが `clone` を返すため、一つのビルダーを複数のクエリで安全に再利用できる
- **Phase 1 の制限**: migrations テーブルによる実行済み管理、OR 条件、JOIN、LIKE は将来の課題
