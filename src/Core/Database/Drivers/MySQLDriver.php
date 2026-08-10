<?php

namespace TightPress\Core\Database\Drivers;

/**
 * MySQL ドライバー。
 *
 * UTF-8（utf8mb4）文字セットを設定し、エミュレートプリペアドステートメントを無効化する。
 * PHP 8.1+ と PDO MySQL 拡張が必要。
 */
class MySQLDriver extends AbstractDriver
{
    /**
     * コンストラクタ。PDO を初期化して MySQL 固有の設定を行う。
     *
     * @param  array<string, mixed> $config MySQL 設定（host, port, database, username, password, charset が対象）
     * @throws \PDOException 接続失敗時
     */
    public function __construct(array $config)
    {
        // 設定値を取り出す（各キーのデフォルト値を設定する）
        $host    = $config['host']     ?? '127.0.0.1';
        $port    = (int) ($config['port'] ?? 3306);
        $dbname  = $config['database'] ?? '';
        $charset = $config['charset']  ?? 'utf8mb4';

        // DSN を組み立てる
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $dbname,
            $charset
        );

        // 認証情報を取り出す
        $username = $config['username'] ?? $config['user'] ?? '';
        $password = $config['password'] ?? '';

        // PDO インスタンスを生成する（エミュレートプリペアドを無効化して型を正確に扱う）
        $this->pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // プレフィックスをバリデーションして保持する
        $prefix = $config['prefix'] ?? 'tp_';
        $this->validatePrefix($prefix);
        $this->prefix = $prefix;
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

        // MySQL は PDO::lastInsertId() で AUTO_INCREMENT の ID を取得できる
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * ドライバー名を返す。
     *
     * @return string 'mysql'
     */
    public function getDriverName(): string
    {
        return 'mysql';
    }
}
