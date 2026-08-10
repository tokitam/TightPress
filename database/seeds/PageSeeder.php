<?php

declare(strict_types=1);

namespace TightPress\Database\Seeds;

use TightPress\Core\Application;
use TightPress\Core\Database\ConnectionInterface;

/**
 * 固定ページサンプルデータ投入シーダー
 *
 * pages テーブルにサンプル固定ページを INSERT する。
 * slug が重複している場合はスキップし、冪等に実行できる。
 */
class PageSeeder
{
    /**
     * データベース接続インスタンス
     *
     * @var ConnectionInterface
     */
    private ConnectionInterface $connection;

    /**
     * コンストラクタ
     *
     * DI コンテナから ConnectionInterface を取得して保持する。
     *
     * @param Application $app DI コンテナ（アプリケーション本体）
     */
    public function __construct(Application $app)
    {
        // DI コンテナから DB 接続を取得する
        $this->connection = $app->make(ConnectionInterface::class);
    }

    /**
     * サンプル固定ページデータを pages テーブルへ投入する
     *
     * slug が既に存在する場合はスキップして冪等性を保つ。
     * 存在しない場合のみ INSERT する。
     *
     * @return void
     */
    public function run(): void
    {
        // 投入するサンプル固定ページデータ
        $pages = [
            [
                'slug'         => 'about',
                'title'        => 'このサイトについて',
                'content'      => '<p>TightPress で作られたサイトです。</p>',
                'status'       => 'publish',
                'sort_order'   => 1,
                'published_at' => '2026-08-01 10:00:00',
                'created_at'   => '2026-08-01 10:00:00',
                'updated_at'   => '2026-08-01 10:00:00',
            ],
            [
                'slug'         => 'contact',
                'title'        => 'お問い合わせ',
                'content'      => '<p>お問い合わせはメールでどうぞ。</p>',
                'status'       => 'publish',
                'sort_order'   => 2,
                'published_at' => '2026-08-01 10:00:00',
                'created_at'   => '2026-08-01 10:00:00',
                'updated_at'   => '2026-08-01 10:00:00',
            ],
        ];

        // 各固定ページを順に処理する
        foreach ($pages as $page) {
            // slug が既に存在するか確認する
            $t        = $this->connection->getPrefix() . 'pages';
            $existing = $this->connection->select(
                "SELECT id FROM {$t} WHERE slug = ?",
                [$page['slug']]
            );

            // 既に存在する場合はスキップする
            if (!empty($existing)) {
                echo "  - スキップ: {$page['slug']} （既に存在）\n";
                continue;
            }

            // pages テーブルへ INSERT する
            // parent_id・author_id・thumbnail_id・template は NULL で投入する
            $this->connection->insert(
                "INSERT INTO {$t}
                    (parent_id, author_id, slug, title, content, status, thumbnail_id, sort_order, template, published_at, created_at, updated_at)
                VALUES
                    (NULL, NULL, ?, ?, ?, ?, NULL, ?, NULL, ?, ?, ?)",
                [
                    $page['slug'],
                    $page['title'],
                    $page['content'],
                    $page['status'],
                    $page['sort_order'],
                    $page['published_at'],
                    $page['created_at'],
                    $page['updated_at'],
                ]
            );

            echo "  + 投入: {$page['slug']}\n";
        }
    }
}
