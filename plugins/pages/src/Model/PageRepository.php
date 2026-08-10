<?php

namespace TightPress\Plugin\Pages\Model;

use TightPress\Core\Database\ConnectionInterface;

/**
 * 固定ページリポジトリクラス。
 * pages テーブルへの CRUD 操作をカプセル化する。
 * ConnectionInterface 経由でデータベースにアクセスし、
 * 取得した行データを Page エンティティへ変換する。
 */
class PageRepository
{
    /**
     * PageRepository を生成する。
     *
     * @param ConnectionInterface $connection データベース接続インターフェース
     */
    public function __construct(
        private readonly ConnectionInterface $connection
    ) {}

    /**
     * スラッグで固定ページを1件取得する。
     *
     * @param  string    $slug 検索するスラッグ
     * @return Page|null       見つかった場合は Page エンティティ、見つからない場合は null
     */
    public function findBySlug(string $slug): ?Page
    {
        $t    = $this->connection->getPrefix() . 'pages';
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
     * IDで固定ページを1件取得する。
     *
     * @param  int       $id 検索するページID
     * @return Page|null     見つかった場合は Page エンティティ、見つからない場合は null
     */
    public function findById(int $id): ?Page
    {
        $t    = $this->connection->getPrefix() . 'pages';
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
     * 公開済み固定ページの一覧を取得する。
     * status が 'publish' の固定ページを sort_order の昇順で全件返す。
     *
     * @return Page[] Page エンティティの配列
     */
    public function findPublished(): array
    {
        $t    = $this->connection->getPrefix() . 'pages';
        $rows = $this->connection->select(
            "SELECT * FROM {$t} WHERE status = ? ORDER BY sort_order ASC",
            ['publish']
        );

        return array_map(
            fn(array $row) => $this->hydrate($row),
            $rows
        );
    }

    /**
     * 固定ページを保存する。
     * id が 0 の場合は INSERT、それ以外は UPDATE を実行する。
     *
     * @param  Page $page 保存する Page エンティティ
     * @return Page       保存後の Page エンティティ（INSERT の場合は新しい ID を持つ）
     */
    public function save(Page $page): Page
    {
        $t           = $this->connection->getPrefix() . 'pages';
        $publishedAt = $page->publishedAt?->format('Y-m-d H:i:s');
        $now         = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        if ($page->id === 0) {
            $id = $this->connection->insert(
                "INSERT INTO {$t} (parent_id, author_id, slug, title, content, status, thumbnail_id, sort_order, template, published_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $page->parentId,
                    $page->authorId,
                    $page->slug,
                    $page->title,
                    $page->content,
                    $page->status,
                    $page->thumbnailId,
                    $page->sortOrder,
                    $page->template,
                    $publishedAt,
                    $now,
                    $now,
                ]
            );

            return $this->findById($id);
        }

        $this->connection->update(
            "UPDATE {$t} SET parent_id = ?, author_id = ?, slug = ?, title = ?, content = ?, status = ?, thumbnail_id = ?, sort_order = ?, template = ?, published_at = ?, updated_at = ?
             WHERE id = ?",
            [
                $page->parentId,
                $page->authorId,
                $page->slug,
                $page->title,
                $page->content,
                $page->status,
                $page->thumbnailId,
                $page->sortOrder,
                $page->template,
                $publishedAt,
                $now,
                $page->id,
            ]
        );

        return $this->findById($page->id);
    }

    /**
     * 固定ページを削除する。
     *
     * @param int $id 削除するページID
     */
    public function delete(int $id): void
    {
        $t = $this->connection->getPrefix() . 'pages';
        $this->connection->delete(
            "DELETE FROM {$t} WHERE id = ?",
            [$id]
        );
    }

    /**
     * データベースの行配列から Page エンティティを生成するヘルパー。
     * 日時カラムは DateTimeImmutable に変換する。
     *
     * @param  array $row pages テーブルの1行分のデータ
     * @return Page       変換された Page エンティティ
     */
    private function hydrate(array $row): Page
    {
        $publishedAt = null;
        if (!empty($row['published_at'])) {
            $publishedAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['published_at']);
            $publishedAt = $publishedAt !== false ? $publishedAt : null;
        }

        $createdAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['created_at']);
        $updatedAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['updated_at']);

        return new Page(
            id: (int) $row['id'],
            parentId: isset($row['parent_id']) ? (int) $row['parent_id'] : null,
            authorId: isset($row['author_id']) ? (int) $row['author_id'] : null,
            slug: $row['slug'],
            title: $row['title'],
            content: $row['content'] ?? '',
            status: $row['status'],
            thumbnailId: isset($row['thumbnail_id']) ? (int) $row['thumbnail_id'] : null,
            sortOrder: (int) $row['sort_order'],
            template: $row['template'] ?? null,
            publishedAt: $publishedAt,
            createdAt: $createdAt !== false ? $createdAt : new \DateTimeImmutable(),
            updatedAt: $updatedAt !== false ? $updatedAt : new \DateTimeImmutable(),
        );
    }
}
