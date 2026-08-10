<?php

namespace TightPress\Plugin\BlogPosts\Model;

use TightPress\Core\Database\ConnectionInterface;

/**
 * ブログ記事リポジトリクラス。
 * posts テーブルへの CRUD 操作をカプセル化する。
 * ConnectionInterface 経由でデータベースにアクセスし、
 * 取得した行データを Post エンティティへ変換する。
 */
class PostRepository
{
    /**
     * PostRepository を生成する。
     *
     * @param ConnectionInterface $connection データベース接続インターフェース
     */
    public function __construct(
        private readonly ConnectionInterface $connection
    ) {}

    /**
     * スラッグで記事を1件取得する。
     *
     * @param  string   $slug 検索するスラッグ
     * @return Post|null      見つかった場合は Post エンティティ、見つからない場合は null
     */
    public function findBySlug(string $slug): ?Post
    {
        $t    = $this->connection->getPrefix() . 'posts';
        $rows = $this->connection->select(
            "SELECT * FROM {$t} WHERE slug = ? LIMIT 1",
            [$slug]
        );

        if (empty($rows)) {
            return null;
        }

        return $this->hydrate($rows[0]);
    }

    /**
     * IDで記事を1件取得する。
     *
     * @param  int      $id 検索する記事ID
     * @return Post|null    見つかった場合は Post エンティティ、見つからない場合は null
     */
    public function findById(int $id): ?Post
    {
        $t    = $this->connection->getPrefix() . 'posts';
        $rows = $this->connection->select(
            "SELECT * FROM {$t} WHERE id = ? LIMIT 1",
            [$id]
        );

        if (empty($rows)) {
            return null;
        }

        return $this->hydrate($rows[0]);
    }

    /**
     * 公開済み記事の一覧を取得する。
     * status が 'publish' の記事を published_at の降順で返す。
     *
     * @param  int     $limit  取得件数（デフォルト: 10）
     * @param  int     $offset 取得開始位置（デフォルト: 0）
     * @return Post[]          Post エンティティの配列
     */
    public function findPublished(int $limit = 10, int $offset = 0): array
    {
        $t    = $this->connection->getPrefix() . 'posts';
        $rows = $this->connection->select(
            "SELECT * FROM {$t} WHERE status = ? ORDER BY published_at DESC LIMIT ? OFFSET ?",
            ['publish', $limit, $offset]
        );

        return array_map(
            fn(array $row) => $this->hydrate($row),
            $rows
        );
    }

    /**
     * 記事を保存する。
     * id が 0 の場合は INSERT、それ以外は UPDATE を実行する。
     *
     * @param  Post $post 保存する Post エンティティ
     * @return Post       保存後の Post エンティティ（INSERT の場合は新しい ID を持つ）
     */
    public function save(Post $post): Post
    {
        $t           = $this->connection->getPrefix() . 'posts';
        $publishedAt = $post->publishedAt?->format('Y-m-d H:i:s');
        $now         = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        if ($post->id === 0) {
            $id = $this->connection->insert(
                "INSERT INTO {$t} (author_id, slug, title, content, excerpt, status, comment_status, thumbnail_id, published_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $post->authorId,
                    $post->slug,
                    $post->title,
                    $post->content,
                    $post->excerpt,
                    $post->status,
                    $post->commentStatus,
                    $post->thumbnailId,
                    $publishedAt,
                    $now,
                    $now,
                ]
            );

            return $this->findById($id);
        }

        $this->connection->update(
            "UPDATE {$t} SET author_id = ?, slug = ?, title = ?, content = ?, excerpt = ?, status = ?, comment_status = ?, thumbnail_id = ?, published_at = ?, updated_at = ?
             WHERE id = ?",
            [
                $post->authorId,
                $post->slug,
                $post->title,
                $post->content,
                $post->excerpt,
                $post->status,
                $post->commentStatus,
                $post->thumbnailId,
                $publishedAt,
                $now,
                $post->id,
            ]
        );

        return $this->findById($post->id);
    }

    /**
     * 記事を削除する。
     *
     * @param int $id 削除する記事ID
     */
    public function delete(int $id): void
    {
        $t = $this->connection->getPrefix() . 'posts';
        $this->connection->delete(
            "DELETE FROM {$t} WHERE id = ?",
            [$id]
        );
    }

    /**
     * データベースの行配列から Post エンティティを生成するヘルパー。
     * 日時カラムは DateTimeImmutable に変換する。
     *
     * @param  array $row posts テーブルの1行分のデータ
     * @return Post       変換された Post エンティティ
     */
    private function hydrate(array $row): Post
    {
        $publishedAt = null;
        if (!empty($row['published_at'])) {
            $publishedAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['published_at']);
            $publishedAt = $publishedAt !== false ? $publishedAt : null;
        }

        $createdAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['created_at']);
        $updatedAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['updated_at']);

        return new Post(
            id: (int) $row['id'],
            authorId: isset($row['author_id']) ? (int) $row['author_id'] : null,
            slug: $row['slug'],
            title: $row['title'],
            content: $row['content'] ?? '',
            excerpt: $row['excerpt'] ?? '',
            status: $row['status'],
            commentStatus: $row['comment_status'],
            thumbnailId: isset($row['thumbnail_id']) ? (int) $row['thumbnail_id'] : null,
            publishedAt: $publishedAt,
            createdAt: $createdAt !== false ? $createdAt : new \DateTimeImmutable(),
            updatedAt: $updatedAt !== false ? $updatedAt : new \DateTimeImmutable(),
        );
    }
}
