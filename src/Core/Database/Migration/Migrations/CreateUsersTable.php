<?php

namespace TightPress\Core\Database\Migration\Migrations;

use TightPress\Core\Database\ConnectionInterface;
use TightPress\Core\Database\Migration\MigrationInterface;

/**
 * users テーブル作成マイグレーション。
 *
 * WordPress の wp_users に相当するユーザーアカウント管理テーブルを作成する。
 * パスワードはハッシュ化した値を保存し、生のパスワードは保持しない。
 */
class CreateUsersTable implements MigrationInterface
{
    /**
     * コンストラクタ。
     *
     * @param ConnectionInterface $connection 使用するデータベース接続
     */
    public function __construct(private readonly ConnectionInterface $connection) {}

    /**
     * users テーブルを作成する。
     *
     * @return void
     */
    public function up(): void
    {
        $prefix = $this->connection->getPrefix();

        $this->connection->execute(
            "CREATE TABLE IF NOT EXISTS {$prefix}users (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                login         VARCHAR(60) NOT NULL UNIQUE,
                email         VARCHAR(100) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                display_name  VARCHAR(250),
                role          VARCHAR(20) NOT NULL DEFAULT 'subscriber',
                status        VARCHAR(20) NOT NULL DEFAULT 'active',
                registered_at DATETIME NOT NULL,
                created_at    DATETIME NOT NULL,
                updated_at    DATETIME NOT NULL
            )"
        );
    }

    /**
     * users テーブルを削除する。
     *
     * @return void
     */
    public function down(): void
    {
        $prefix = $this->connection->getPrefix();
        $this->connection->execute("DROP TABLE IF EXISTS {$prefix}users");
    }
}
