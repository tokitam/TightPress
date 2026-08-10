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
        // posts テーブルを作成する
        // ブログ記事の基本情報を格納する
        $this->connection->update(
            'CREATE TABLE IF NOT EXISTS posts (
                id             INTEGER PRIMARY KEY AUTOINCREMENT,
                author_id      INTEGER REFERENCES users(id) ON DELETE SET NULL,
                slug           VARCHAR(191) NOT NULL UNIQUE,
                title          VARCHAR(500) NOT NULL,
                content        TEXT,
                excerpt        TEXT,
                status         VARCHAR(20) NOT NULL DEFAULT \'draft\',
                comment_status VARCHAR(20) NOT NULL DEFAULT \'open\',
                thumbnail_id   INTEGER REFERENCES media(id) ON DELETE SET NULL,
                published_at   DATETIME,
                created_at     DATETIME NOT NULL,
                updated_at     DATETIME NOT NULL
            )',
            []
        );

        // posts テーブルの status カラムにインデックスを作成する
        // 公開済み記事の絞り込みを高速化するために使用する
        $this->connection->update(
            'CREATE INDEX IF NOT EXISTS idx_posts_status ON posts (status)',
            []
        );

        // posts テーブルの published_at カラムにインデックスを作成する
        // 公開日時降順での記事一覧取得を高速化するために使用する
        $this->connection->update(
            'CREATE INDEX IF NOT EXISTS idx_posts_published_at ON posts (published_at)',
            []
        );

        // post_meta テーブルを作成する
        // 記事に紐付くカスタムメタデータを格納する
        $this->connection->update(
            'CREATE TABLE IF NOT EXISTS post_meta (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id    INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
                meta_key   VARCHAR(191) NOT NULL,
                meta_value TEXT,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )',
            []
        );

        // post_meta テーブルの post_id + meta_key にインデックスを作成する
        // 特定記事のメタデータを素早く検索するために使用する
        $this->connection->update(
            'CREATE INDEX IF NOT EXISTS idx_post_meta_post_id_key ON post_meta (post_id, meta_key)',
            []
        );

        // post_term_relationships テーブルを作成する
        // 記事とカテゴリー・タグ（タクソノミータームとの中間テーブル）
        $this->connection->update(
            'CREATE TABLE IF NOT EXISTS post_term_relationships (
                post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
                term_id INTEGER NOT NULL REFERENCES terms(id) ON DELETE CASCADE,
                PRIMARY KEY (post_id, term_id)
            )',
            []
        );

        // post_term_relationships テーブルの term_id にインデックスを作成する
        // 特定タームに属する記事の検索を高速化するために使用する
        $this->connection->update(
            'CREATE INDEX IF NOT EXISTS idx_post_term_relationships_term_id ON post_term_relationships (term_id)',
            []
        );

        // comments テーブルを作成する
        // 記事に投稿されたコメントを格納する
        $this->connection->update(
            'CREATE TABLE IF NOT EXISTS comments (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id      INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
                parent_id    INTEGER REFERENCES comments(id) ON DELETE CASCADE,
                author_id    INTEGER REFERENCES users(id) ON DELETE SET NULL,
                author_name  VARCHAR(255),
                author_email VARCHAR(100),
                content      TEXT NOT NULL,
                status       VARCHAR(20) NOT NULL DEFAULT \'pending\',
                created_at   DATETIME NOT NULL,
                updated_at   DATETIME NOT NULL
            )',
            []
        );

        // comments テーブルの post_id にインデックスを作成する
        // 特定記事のコメント一覧取得を高速化するために使用する
        $this->connection->update(
            'CREATE INDEX IF NOT EXISTS idx_comments_post_id ON comments (post_id)',
            []
        );

        // comments テーブルの status にインデックスを作成する
        // 承認済みコメントの絞り込みを高速化するために使用する
        $this->connection->update(
            'CREATE INDEX IF NOT EXISTS idx_comments_status ON comments (status)',
            []
        );
    }

    /**
     * テーブルを削除する（プラグインアンインストール時に実行）。
     * 外部キー制約の依存順序に従い、子テーブルから先に削除する。
     * comments → post_term_relationships → post_meta → posts の順に削除する。
     */
    public function down(): void
    {
        // comments テーブルを削除する（posts に依存しているため先に削除）
        $this->connection->update(
            'DROP TABLE IF EXISTS comments',
            []
        );

        // post_term_relationships テーブルを削除する（posts に依存しているため先に削除）
        $this->connection->update(
            'DROP TABLE IF EXISTS post_term_relationships',
            []
        );

        // post_meta テーブルを削除する（posts に依存しているため先に削除）
        $this->connection->update(
            'DROP TABLE IF EXISTS post_meta',
            []
        );

        // posts テーブルを削除する（全依存テーブルの削除後に削除）
        $this->connection->update(
            'DROP TABLE IF EXISTS posts',
            []
        );
    }
}
