<?php

namespace TightPress\Core\Database\Drivers;

/**
 * PostgreSQL ドライバー。
 *
 * INSERT の ID 取得に lastInsertId() ではなく RETURNING id 句を使う。
 * PHP 8.1+ と PDO PostgreSQL 拡張が必要。
 */
class PostgreSQLDriver extends AbstractDriver
{
    /**
     * コンストラクタ。PDO を初期化する。
     *
     * @param  array<string, mixed> $config PostgreSQL 設定（host, port, database, username, password が対象）
     * @throws \PDOException 接続失敗時
     */
    public function __construct(array $config)
    {
        // 設定値を取り出す（各キーのデフォルト値を設定する）
        $host   = $config['host']     ?? '127.0.0.1';
        $port   = (int) ($config['port'] ?? 5432);
        $dbname = $config['database'] ?? '';

        // DSN を組み立てる
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $host,
            $port,
            $dbname
        );

        // 認証情報を取り出す
        $username = $config['username'] ?? $config['user'] ?? '';
        $password = $config['password'] ?? '';

        // PDO インスタンスを生成する
        $this->pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
    }

    /**
     * INSERT を実行して挿入されたレコードの ID を返す。
     *
     * PostgreSQL は lastInsertId() が信頼性に欠けるため RETURNING id 句を使う。
     * SQL に RETURNING id が含まれていない場合は末尾に自動追加する。
     *
     * @param  string $sql      実行する INSERT 文
     * @param  array<int|string, mixed> $bindings プレースホルダーのバインド値
     * @return int 挿入した行の ID
     */
    public function insert(string $sql, array $bindings = []): int
    {
        // RETURNING id が含まれていない場合は末尾に追加する
        if (!str_contains(strtoupper($sql), 'RETURNING ID')) {
            $sql = rtrim($sql, '; ') . ' RETURNING id';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        // RETURNING id の結果から ID を取得する
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? (int) $row['id'] : 0;
    }

    /**
     * ドライバー名を返す。
     *
     * @return string 'pgsql'
     */
    public function getDriverName(): string
    {
        return 'pgsql';
    }
}
