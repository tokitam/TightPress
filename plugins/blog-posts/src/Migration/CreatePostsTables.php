<?php

namespace TightPress\Plugin\BlogPosts\Migration;

use TightPress\Core\Database\ConnectionInterface;

/**
 * ブログ記事関連テーブルのマイグレーションクラス。
 * posts / post_meta / post_term_relationships / comments の4テーブルを管理する。
 * SQLite 互換の DDL を使用する。
 */
class CreatePostsTables
{
    /**
     * CreatePostsTables を生成する。
     *
     * @param ConnectionInterface $connection データベース接続インターフェース
     */
    public function __construct(
        private readonly ConnectionInterface $connection
    ) {}

    /**
     * テーブルを作成する（プラグインインストール時に実行）。
     * posts → post_meta → post_term_relationships → comments の順に作成する。
     * 既にテーブルが存在する場合はスキップする（IF NOT EXISTS）。
     */
    public function up(): void
    {
        $p = $this->connection->getPrefix();

        // posts テーブルを作成する
        $this->connection->execute(
            "CREATE TABLE IF NOT EXISTS {$p}posts (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                author_id      INTEGER REFERENCES {$p}users(id) ON DELETE SET NULL,
                slug           VARCHAR(191) NOT NULL UNIQUE,
                title          VARCHAR(500) NOT NULL,
                content        TEXT,
                excerpt        TEXT,
                status         VARCHAR(20) NOT NULL DEFAULT 'draft',
                comment_status VARCHAR(20) NOT NULL DEFAULT 'open',
                thumbnail_id   INTEGER REFERENCES {$p}media(id) ON DELETE SET NULL,
                published_at   DATETIME,
                created_at     DATETIME NOT NULL,
                updated_at     DATETIME NOT NULL
            )"
        );

        $this->connection->execute(
            "CREATE INDEX IF NOT EXISTS idx_{$p}posts_status ON {$p}posts (status)"
        );

        $this->connection->execute(
            "CREATE INDEX IF NOT EXISTS idx_{$p}posts_published_at ON {$p}posts (published_at)"
        );

        // post_meta テーブルを作成する
        $this->connection->execute(
            "CREATE TABLE IF NOT EXISTS {$p}post_meta (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id    INTEGER NOT NULL REFERENCES {$p}posts(id) ON DELETE CASCADE,
                meta_key   VARCHAR(191) NOT NULL,
                meta_value TEXT,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )"
        );

        $this->connection->execute(
            "CREATE INDEX IF NOT EXISTS idx_{$p}post_meta_post_id_key ON {$p}post_meta (post_id, meta_key)"
        );

        // post_term_relationships テーブルを作成する
        $this->connection->execute(
            "CREATE TABLE IF NOT EXISTS {$p}post_term_relationships (
                post_id INTEGER NOT NULL REFERENCES {$p}posts(id) ON DELETE CASCADE,
                term_id INTEGER NOT NULL REFERENCES {$p}terms(id) ON DELETE CASCADE,
                PRIMARY KEY (post_id, term_id)
            )"
        );

        $this->connection->execute(
            "CREATE INDEX IF NOT EXISTS idx_{$p}post_term_relationships_term_id ON {$p}post_term_relationships (term_id)"
        );

        // comments テーブルを作成する
        $this->connection->execute(
            "CREATE TABLE IF NOT EXISTS {$p}comments (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id      INTEGER NOT NULL REFERENCES {$p}posts(id) ON DELETE CASCADE,
                parent_id    INTEGER REFERENCES {$p}comments(id) ON DELETE CASCADE,
                author_id    INTEGER REFERENCES {$p}users(id) ON DELETE SET NULL,
                author_name  VARCHAR(255),
                author_email VARCHAR(100),
                content      TEXT NOT NULL,
                status       VARCHAR(20) NOT NULL DEFAULT 'pending',
                created_at   DATETIME NOT NULL,
                updated_at   DATETIME NOT NULL
            )"
        );

        $this->connection->execute(
            "CREATE INDEX IF NOT EXISTS idx_{$p}comments_post_id ON {$p}comments (post_id)"
        );

        $this->connection->execute(
            "CREATE INDEX IF NOT EXISTS idx_{$p}comments_status ON {$p}comments (status)"
        );
    }

    /**
     * テーブルを削除する（プラグインアンインストール時に実行）。
     * 外部キー制約の依存順序に従い、子テーブルから先に削除する。
     * comments → post_term_relationships → post_meta → posts の順に削除する。
     */
    public function down(): void
    {
        $p = $this->connection->getPrefix();

        $this->connection->execute("DROP TABLE IF EXISTS {$p}comments");
        $this->connection->execute("DROP TABLE IF EXISTS {$p}post_term_relationships");
        $this->connection->execute("DROP TABLE IF EXISTS {$p}post_meta");
        $this->connection->execute("DROP TABLE IF EXISTS {$p}posts");
    }
}
