<?php

declare(strict_types=1);

namespace TightPress\Database\Seeds;

use TightPress\Core\Application;
use TightPress\Core\Database\ConnectionInterface;

/**
 * 記事サンプルデータ投入シーダー
 *
 * posts テーブルにサンプル記事を INSERT する。
 * slug が重複している場合はスキップし、冪等に実行できる。
 */
class PostSeeder
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
     * サンプル記事データを posts テーブルへ投入する
     *
     * slug が既に存在する場合はスキップして冪等性を保つ。
     * 存在しない場合のみ INSERT する。
     *
     * @return void
     */
    public function run(): void
    {
        // 投入するサンプル記事データ
        $posts = [
            [
                'slug'         => 'hello-world',
                'title'        => 'Hello World',
                'content'      => '<p>TightPress へようこそ。最初の記事です。</p>',
                'excerpt'      => 'TightPress へようこそ。',
                'status'       => 'publish',
                'published_at' => '2026-08-01 10:00:00',
                'created_at'   => '2026-08-01 10:00:00',
                'updated_at'   => '2026-08-01 10:00:00',
            ],
            [
                'slug'         => 'second-post',
                'title'        => '2番目の記事',
                'content'      => '<p>これは2番目の記事です。</p>',
                'excerpt'      => 'これは2番目の記事です。',
                'status'       => 'publish',
                'published_at' => '2026-08-05 12:00:00',
                'created_at'   => '2026-08-05 12:00:00',
                'updated_at'   => '2026-08-05 12:00:00',
            ],
            [
                'slug'         => 'third-post',
                'title'        => '3番目の記事',
                'content'      => '<p>これは3番目の記事です。</p>',
                'excerpt'      => 'これは3番目の記事です。',
                'status'       => 'publish',
                'published_at' => '2026-08-10 09:00:00',
                'created_at'   => '2026-08-10 09:00:00',
                'updated_at'   => '2026-08-10 09:00:00',
            ],
        ];

        // 各記事を順に処理する
        foreach ($posts as $post) {
            // slug が既に存在するか確認する
            $existing = $this->connection->select(
                'SELECT id FROM posts WHERE slug = ?',
                [$post['slug']]
            );

            // 既に存在する場合はスキップする
            if (!empty($existing)) {
                echo "  - スキップ: {$post['slug']} （既に存在）\n";
                continue;
            }

            // posts テーブルへ INSERT する
            // author_id・comment_status・thumbnail_id は NULL で投入する
            $this->connection->insert(
                'INSERT INTO posts
                    (author_id, slug, title, content, excerpt, status, comment_status, thumbnail_id, published_at, created_at, updated_at)
                VALUES
                    (NULL, ?, ?, ?, ?, ?, \'open\', NULL, ?, ?, ?)',
                [
                    $post['slug'],
                    $post['title'],
                    $post['content'],
                    $post['excerpt'],
                    $post['status'],
                    $post['published_at'],
                    $post['created_at'],
                    $post['updated_at'],
                ]
            );

            echo "  + 投入: {$post['slug']}\n";
        }
    }
}
