<?php

namespace TightPress\Core\Database\Drivers;

use TightPress\Core\Database\ConnectionInterface;

/**
 * データベースドライバーの共通処理を実装する抽象クラス。
 *
 * SELECT / UPDATE / DELETE / transaction / getPdo など
 * ドライバー間で共通のロジックをここに集約する。
 * INSERT だけは PostgreSQL の RETURNING id 対応があるためサブクラスで実装する。
 */
abstract class AbstractDriver implements ConnectionInterface
{
    /**
     * PDO インスタンス（各サブクラスのコンストラクタで初期化する）。
     *
     * @var \PDO
     */
    protected \PDO $pdo;

    /**
     * プリペアドステートメントで SELECT を実行して結果行の配列を返す。
     *
     * @param  string $sql      実行する SELECT 文
     * @param  array<int|string, mixed> $bindings プレースホルダーのバインド値
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $bindings = []): array
    {
        // ステートメントを準備してバインド・実行する
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        // 全行を連想配列で取得して返す
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * UPDATE を実行して影響を受けた行数を返す。
     *
     * @param  string $sql      実行する UPDATE 文
     * @param  array<int|string, mixed> $bindings プレースホルダーのバインド値
     * @return int 影響行数
     */
    public function update(string $sql, array $bindings = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->rowCount();
    }

    /**
     * DELETE を実行して影響を受けた行数を返す。
     *
     * @param  string $sql      実行する DELETE 文
     * @param  array<int|string, mixed> $bindings プレースホルダーのバインド値
     * @return int 影響行数
     */
    public function delete(string $sql, array $bindings = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->rowCount();
    }

    /**
     * トランザクション内でコールバックを実行する。
     *
     * 例外が発生した場合はロールバックして再スローする。
     *
     * @param  callable $callback 実行するコールバック
     * @return mixed コールバックの戻り値
     * @throws \Throwable コールバック内で発生した例外
     */
    public function transaction(callable $callback): mixed
    {
        // トランザクション開始
        $this->pdo->beginTransaction();

        try {
            // コールバックを実行する
            $result = $callback($this);

            // 正常終了したらコミットする
            $this->pdo->commit();

            return $result;
        } catch (\Throwable $e) {
            // 例外が発生したらロールバックして再スローする
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * 生の PDO オブジェクトを返す。
     *
     * @return \PDO
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
}
