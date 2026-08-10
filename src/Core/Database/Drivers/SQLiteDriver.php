<?php

namespace TightPress\Core\Database\Drivers;

/**
 * SQLite ドライバー。
 *
 * WAL モードを有効化し、マルチプロセスからの書き込みに備えたタイムアウトを設定する。
 * PHP 8.1+ と PDO SQLite 拡張が必要。
 */
class SQLiteDriver extends AbstractDriver
{
    /**
     * コンストラクタ。PDO を初期化して SQLite 固有の設定を行う。
     *
     * @param  array<string, mixed> $config SQLite 設定（'path' キーが必須）
     * @throws \PDOException 接続失敗時
     */
    public function __construct(array $config)
    {
        // データベースファイルのパスから DSN を組み立てる
        $path = $config['path'] ?? ':memory:';
        $dsn  = "sqlite:{$path}";

        // PDO インスタンスを生成する（エラーは例外、タイムアウト設定）
        $this->pdo = new \PDO($dsn, null, null, [
            \PDO::ATTR_ERRMODE    => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_TIMEOUT    => 5,
        ]);

        // WAL（Write-Ahead Logging）モードを有効化して書き込み並行性を高める
        $this->pdo->exec('PRAGMA journal_mode=WAL;');
    }

    /**
     * INSERT を実行して最後に挿入されたレコードの ID を返す。
     *
     * @param  string $sql      実行する INSERT 文
     * @param  array<int|string, mixed> $bindings プレースホルダーのバインド値
     * @return int 挿入した行の ID
     */
    public function insert(string $sql, array $bindings = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        // SQLite は PDO::lastInsertId() で最後の挿入 ID を取得できる
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * ドライバー名を返す。
     *
     * @return string 'sqlite'
     */
    public function getDriverName(): string
    {
        return 'sqlite';
    }
}
