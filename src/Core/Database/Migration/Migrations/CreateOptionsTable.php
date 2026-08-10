<?php

namespace TightPress\Core\Database\Migration\Migrations;

use TightPress\Core\Database\ConnectionInterface;
use TightPress\Core\Database\Migration\MigrationInterface;

/**
 * options テーブル作成マイグレーション。
 *
 * WordPress の wp_options に相当するサイト設定保存テーブルを作成する。
 * autoload フラグで起動時に一括読み込みする設定を識別できる。
 */
class CreateOptionsTable implements MigrationInterface
{
    /**
     * コンストラクタ。
     *
     * @param ConnectionInterface $connection 使用するデータベース接続
     */
    public function __construct(private readonly ConnectionInterface $connection) {}

    /**
     * options テーブルを作成する。
     *
     * @return void
     */
    public function up(): void
    {
        $prefix = $this->connection->getPrefix();

        $this->connection->execute(
            "CREATE TABLE IF NOT EXISTS {$prefix}options (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       VARCHAR(191) NOT NULL UNIQUE,
                value      TEXT,
                autoload   BOOLEAN NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )"
        );
    }

    /**
     * options テーブルを削除する。
     *
     * @return void
     */
    public function down(): void
    {
        $prefix = $this->connection->getPrefix();
        $this->connection->execute("DROP TABLE IF EXISTS {$prefix}options");
    }
}
