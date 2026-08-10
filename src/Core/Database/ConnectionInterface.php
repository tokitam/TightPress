<?php

namespace TightPress\Core\Database;

/**
 * データベース接続インターフェース。
 *
 * SQLite / MySQL / PostgreSQL の各ドライバーが実装する共通契約。
 * プリペアドステートメントを使った安全な SQL 実行を提供する。
 */
interface ConnectionInterface
{
    /**
     * SELECT を実行して結果行の配列を返す。
     *
     * @param  string $sql      実行する SELECT 文
     * @param  array<int|string, mixed> $bindings プレースホルダーのバインド値
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $bindings = []): array;

    /**
     * INSERT を実行して最後に挿入されたレコードの ID を返す。
     *
     * @param  string $sql      実行する INSERT 文
     * @param  array<int|string, mixed> $bindings プレースホルダーのバインド値
     * @return int 挿入した行の ID
     */
    public function insert(string $sql, array $bindings = []): int;

    /**
     * UPDATE を実行して影響を受けた行数を返す。
     *
     * @param  string $sql      実行する UPDATE 文
     * @param  array<int|string, mixed> $bindings プレースホルダーのバインド値
     * @return int 影響行数
     */
    public function update(string $sql, array $bindings = []): int;

    /**
     * DELETE を実行して影響を受けた行数を返す。
     *
     * @param  string $sql      実行する DELETE 文
     * @param  array<int|string, mixed> $bindings プレースホルダーのバインド値
     * @return int 影響行数
     */
    public function delete(string $sql, array $bindings = []): int;

    /**
     * DDL・DML を汎用的に実行して影響行数を返す。
     *
     * CREATE TABLE / DROP TABLE などのスキーマ操作に使用する。
     *
     * @param  string $sql      実行する SQL 文
     * @param  array<int|string, mixed> $bindings プレースホルダーのバインド値
     * @return int 影響行数（DDL では 0 が返ることがある）
     */
    public function execute(string $sql, array $bindings = []): int;

    /**
     * トランザクション内でコールバックを実行する。
     *
     * コールバックが例外を投げた場合はロールバックされ、例外が再スローされる。
     * 正常終了した場合はコミットされ、コールバックの戻り値を返す。
     *
     * @param  callable $callback 実行するコールバック
     * @return mixed コールバックの戻り値
     */
    public function transaction(callable $callback): mixed;

    /**
     * ドライバー名を返す。
     *
     * @return string 'sqlite' | 'mysql' | 'pgsql' のいずれか
     */
    public function getDriverName(): string;

    /**
     * テーブルプレフィックスを返す。
     *
     * @return string プレフィックス文字列（例: 'tp_'）。プレフィックスなしの場合は空文字
     */
    public function getPrefix(): string;

    /**
     * 生の PDO オブジェクトを返す。
     *
     * マイグレーションなど DDL を直接実行する場面で使用する。
     *
     * @return \PDO
     */
    public function getPdo(): \PDO;
}
