<?php

namespace TightPress\Core\Database\Migration\Migrations;

use TightPress\Core\Database\ConnectionInterface;
use TightPress\Core\Database\Migration\MigrationInterface;

/**
 * terms テーブル作成マイグレーション。
 *
 * タクソノミーに属する各ターム（カテゴリー・タグなど）を保存するテーブルを作成する。
 * WordPress の wp_terms と wp_term_taxonomy を統合した構造となっている。
 * 階層的なターム（親子関係）に対応するため self 参照の parent_id を持つ。
 */
class CreateTermsTable implements MigrationInterface
{
    /**
     * コンストラクタ。
     *
     * @param ConnectionInterface $connection 使用するデータベース接続
     */
    public function __construct(private readonly ConnectionInterface $connection) {}

    /**
     * terms テーブルを作成する。
     *
     * SQLite では CREATE TABLE 内に INDEX を記述できないため、
     * テーブル作成後に別途 CREATE INDEX を実行する。
     *
     * @return void
     */
    public function up(): void
    {
        $prefix = $this->connection->getPrefix();

        $this->connection->execute(
            "CREATE TABLE IF NOT EXISTS {$prefix}terms (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                taxonomy_id INTEGER NOT NULL REFERENCES {$prefix}taxonomies(id) ON DELETE CASCADE,
                parent_id   INTEGER REFERENCES {$prefix}terms(id) ON DELETE SET NULL,
                slug        VARCHAR(191) NOT NULL,
                name        VARCHAR(255) NOT NULL,
                description TEXT,
                sort_order  INTEGER NOT NULL DEFAULT 0,
                created_at  DATETIME NOT NULL,
                updated_at  DATETIME NOT NULL,
                UNIQUE (taxonomy_id, slug)
            )"
        );

        $this->connection->execute(
            "CREATE INDEX IF NOT EXISTS idx_{$prefix}terms_taxonomy_id ON {$prefix}terms (taxonomy_id)"
        );

        $this->connection->execute(
            "CREATE INDEX IF NOT EXISTS idx_{$prefix}terms_parent_id ON {$prefix}terms (parent_id)"
        );
    }

    /**
     * terms テーブルを削除する。
     *
     * @return void
     */
    public function down(): void
    {
        $prefix = $this->connection->getPrefix();
        $this->connection->execute("DROP TABLE IF EXISTS {$prefix}terms");
    }
}
