<?php

namespace TightPress\Plugin\Pages\Migration;

use TightPress\Core\Database\ConnectionInterface;

/**
 * pages / page_meta テーブルの作成・削除を行うマイグレーションクラス。
 * SQLite 互換の DDL を使用する。
 */
class CreatePagesTables
{
    /**
     * CreatePagesTables を生成する。
     *
     * @param ConnectionInterface $connection データベース接続インターフェース
     */
    public function __construct(
        private readonly ConnectionInterface $connection
    ) {}

    /**
     * テーブルを作成する。
     *
     * pages テーブルおよび page_meta テーブルを CREATE TABLE IF NOT EXISTS で作成する。
     * 既にテーブルが存在する場合は何もしない。
     *
     * @return void
     */
    public function up(): void
    {
        $p = $this->connection->getPrefix();

        // pages テーブルを作成する
        $this->connection->execute(
            "CREATE TABLE IF NOT EXISTS {$p}pages (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                parent_id    INTEGER REFERENCES {$p}pages(id) ON DELETE SET NULL,
                author_id    INTEGER REFERENCES {$p}users(id) ON DELETE SET NULL,
                slug         VARCHAR(191) NOT NULL UNIQUE,
                title        VARCHAR(500) NOT NULL,
                content      LONGTEXT,
                status       VARCHAR(20) NOT NULL DEFAULT 'draft',
                thumbnail_id INTEGER REFERENCES {$p}media(id) ON DELETE SET NULL,
                sort_order   INTEGER NOT NULL DEFAULT 0,
                template     VARCHAR(255),
                published_at DATETIME,
                created_at   DATETIME NOT NULL,
                updated_at   DATETIME NOT NULL
            )"
        );

        // page_meta テーブルを作成する
        $this->connection->execute(
            "CREATE TABLE IF NOT EXISTS {$p}page_meta (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                page_id    INTEGER NOT NULL REFERENCES {$p}pages(id) ON DELETE CASCADE,
                meta_key   VARCHAR(191) NOT NULL,
                meta_value TEXT,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )"
        );
    }

    /**
     * テーブルを削除する。
     *
     * page_meta → pages の順に DROP TABLE IF EXISTS で削除する。
     * 外部キー制約により page_meta を先に削除する必要がある。
     *
     * @return void
     */
    public function down(): void
    {
        $p = $this->connection->getPrefix();

        $this->connection->execute("DROP TABLE IF EXISTS {$p}page_meta");
        $this->connection->execute("DROP TABLE IF EXISTS {$p}pages");
    }
}
