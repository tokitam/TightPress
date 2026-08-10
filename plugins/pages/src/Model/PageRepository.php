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
        // スラッグが一致するレコードを1件取得する
        $rows = $this->connection->select(
            'SELECT * FROM pages WHERE slug = ? LIMIT 1',
            [$slug]
        );

        // レコードが存在しない場合は null を返す
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
        // IDが一致するレコードを1件取得する
        $rows = $this->connection->select(
            'SELECT * FROM pages WHERE id = ? LIMIT 1',
            [$id]
        );

        // レコードが存在しない場合は null を返す
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
        // status='publish' の固定ページを sort_order 昇順で全件取得する
        $rows = $this->connection->select(
            'SELECT * FROM pages WHERE status = ? ORDER BY sort_order ASC',
            ['publish']
        );

        // 各行を Page エンティティに変換して返す
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
        // published_at を文字列に変換する（null の場合はそのまま null）
        $publishedAt = $page->publishedAt?->format('Y-m-d H:i:s');

        // 現在日時を文字列で取得する
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        if ($page->id === 0) {
            // id が 0 の場合は新規 INSERT を実行する
            $id = $this->connection->insert(
                'INSERT INTO pages (parent_id, author_id, slug, title, content, status, thumbnail_id, sort_order, template, published_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
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

            // 新しい ID で Page エンティティを生成して返す
            return $this->findById($id);
        }

        // id が 0 以外の場合は UPDATE を実行する
        $this->connection->update(
            'UPDATE pages SET parent_id = ?, author_id = ?, slug = ?, title = ?, content = ?, status = ?, thumbnail_id = ?, sort_order = ?, template = ?, published_at = ?, updated_at = ?
             WHERE id = ?',
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

        // 更新後の最新データを取得して返す
        return $this->findById($page->id);
    }

    /**
     * 固定ページを削除する。
     *
     * @param int $id 削除するページID
     */
    public function delete(int $id): void
    {
        // 指定IDの固定ページを削除する
        $this->connection->delete(
            'DELETE FROM pages WHERE id = ?',
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
        // published_at が設定されている場合のみ DateTimeImmutable に変換する
        $publishedAt = null;
        if (!empty($row['published_at'])) {
            $publishedAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['published_at']);
            // 変換に失敗した場合は null のまま
            $publishedAt = $publishedAt !== false ? $publishedAt : null;
        }

        // created_at を DateTimeImmutable に変換する
        $createdAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['created_at']);

        // updated_at を DateTimeImmutable に変換する
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
