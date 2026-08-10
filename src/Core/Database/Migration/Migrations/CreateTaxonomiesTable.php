<?php

namespace TightPress\Core\Database\Migration\Migrations;

use TightPress\Core\Database\ConnectionInterface;
use TightPress\Core\Database\Migration\MigrationInterface;

/**
 * taxonomies テーブル作成マイグレーション。
 *
 * タクソノミー（分類体系）の定義を保存するテーブルを作成する。
 * WordPress の wp_term_taxonomy に相当するが、TightPress では
 * タクソノミー定義自体もデータベースで管理する。
 */
class CreateTaxonomiesTable implements MigrationInterface
{
    /**
     * コンストラクタ。
     *
     * @param ConnectionInterface $connection 使用するデータベース接続
     */
    public function __construct(private readonly ConnectionInterface $connection) {}

    /**
     * taxonomies テーブルを作成する。
     *
     * @return void
     */
    public function up(): void
    {
        $prefix = $this->connection->getPrefix();

        $this->connection->execute(
            "CREATE TABLE IF NOT EXISTS {$prefix}taxonomies (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                slug         VARCHAR(191) NOT NULL UNIQUE,
                label        VARCHAR(255) NOT NULL,
                description  TEXT,
                hierarchical BOOLEAN NOT NULL DEFAULT 0,
                created_at   DATETIME NOT NULL,
                updated_at   DATETIME NOT NULL
            )"
        );
    }

    /**
     * taxonomies テーブルを削除する。
     *
     * terms テーブルが外部キーで参照しているため、先に terms を削除する必要がある。
     *
     * @return void
     */
    public function down(): void
    {
        $prefix = $this->connection->getPrefix();
        $this->connection->execute("DROP TABLE IF EXISTS {$prefix}taxonomies");
    }
}
