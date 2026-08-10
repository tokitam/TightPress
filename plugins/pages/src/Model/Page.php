<?php

namespace TightPress\Plugin\Pages\Model;

/**
 * 固定ページエンティティクラス。
 * pages テーブルの1行を表すイミュータブルな値オブジェクト。
 */
class Page
{
    /**
     * Page エンティティを生成する。
     *
     * @param int                        $id          ページID
     * @param int|null                   $parentId    親ページのID（トップレベルページの場合は null）
     * @param int|null                   $authorId    投稿者のユーザーID（投稿者が削除された場合は null）
     * @param string                     $slug        URLスラッグ（一意）
     * @param string                     $title       ページタイトル
     * @param string                     $content     ページ本文（HTML）
     * @param string                     $status      公開ステータス（draft / publish 等）
     * @param int|null                   $thumbnailId アイキャッチ画像のメディアID（未設定時は null）
     * @param int                        $sortOrder   同一階層内での表示順序
     * @param string|null                $template    使用するテンプレートファイル名（未設定時は null）
     * @param \DateTimeImmutable|null    $publishedAt 公開日時（未公開時は null）
     * @param \DateTimeImmutable         $createdAt   作成日時
     * @param \DateTimeImmutable         $updatedAt   更新日時
     */
    public function __construct(
        public readonly int     $id,
        public readonly ?int    $parentId,
        public readonly ?int    $authorId,
        public readonly string  $slug,
        public readonly string  $title,
        public readonly string  $content,
        public readonly string  $status,
        public readonly ?int    $thumbnailId,
        public readonly int     $sortOrder,
        public readonly ?string $template,
        public readonly ?\DateTimeImmutable $publishedAt,
        public readonly \DateTimeImmutable  $createdAt,
        public readonly \DateTimeImmutable  $updatedAt,
    ) {}
}
