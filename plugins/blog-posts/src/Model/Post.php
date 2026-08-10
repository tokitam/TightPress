<?php

namespace TightPress\Plugin\BlogPosts\Model;

/**
 * ブログ記事エンティティクラス。
 * posts テーブルの1行を表すイミュータブルな値オブジェクト。
 */
class Post
{
    /**
     * Post エンティティを生成する。
     *
     * @param int                        $id            記事ID
     * @param int|null                   $authorId      投稿者のユーザーID（投稿者が削除された場合は null）
     * @param string                     $slug          URLスラッグ（一意）
     * @param string                     $title         記事タイトル
     * @param string                     $content       記事本文（HTML）
     * @param string                     $excerpt       記事抜粋
     * @param string                     $status        公開ステータス（draft / publish / private 等）
     * @param string                     $commentStatus コメントの受付状態（open / closed）
     * @param int|null                   $thumbnailId   アイキャッチ画像のメディアID（未設定時は null）
     * @param \DateTimeImmutable|null    $publishedAt   公開日時（未公開時は null）
     * @param \DateTimeImmutable         $createdAt     作成日時
     * @param \DateTimeImmutable         $updatedAt     更新日時
     */
    public function __construct(
        public readonly int     $id,
        public readonly ?int    $authorId,
        public readonly string  $slug,
        public readonly string  $title,
        public readonly string  $content,
        public readonly string  $excerpt,
        public readonly string  $status,
        public readonly string  $commentStatus,
        public readonly ?int    $thumbnailId,
        public readonly ?\DateTimeImmutable $publishedAt,
        public readonly \DateTimeImmutable  $createdAt,
        public readonly \DateTimeImmutable  $updatedAt,
    ) {}
}
