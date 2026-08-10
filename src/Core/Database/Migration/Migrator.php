<?php

namespace TightPress\Core\Database\Migration;

use TightPress\Core\Database\ConnectionInterface;

/**
 * マイグレーター。
 *
 * 登録されたマイグレーションを管理し、順番に up() または down() を呼び出す。
 * Phase 1 では migrations テーブルによる実行済み管理は行わず、
 * シンプルに全 up() を実行する方式とする。
 */
class Migrator
{
    /**
     * 登録されたマイグレーションのリスト（追加順を保証する）。
     *
     * @var list<MigrationInterface>
     */
    private array $migrations = [];

    /**
     * コンストラクタ。
     *
     * @param ConnectionInterface $connection 使用するデータベース接続
     */
    public function __construct(private readonly ConnectionInterface $connection) {}

    /**
     * マイグレーションを登録する。
     *
     * @param  MigrationInterface $migration 登録するマイグレーションインスタンス
     * @return void
     */
    public function add(MigrationInterface $migration): void
    {
        $this->migrations[] = $migration;
    }

    /**
     * 登録済みの全マイグレーションを順番に実行する（up）。
     *
     * Phase 1 では実行済みチェックは行わず、登録順にすべて up() を呼び出す。
     *
     * @return void
     */
    public function run(): void
    {
        // 登録順に up() を呼び出す
        foreach ($this->migrations as $migration) {
            $migration->up();
        }
    }

    /**
     * 登録済みの全マイグレーションを逆順でロールバックする（down）。
     *
     * @return void
     */
    public function rollback(): void
    {
        // 登録と逆順に down() を呼び出す（依存関係を考慮して逆順にする）
        foreach (array_reverse($this->migrations) as $migration) {
            $migration->down();
        }
    }
}
