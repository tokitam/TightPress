<?php

namespace TightPress\Plugin\BlogPosts\Events;

use TightPress\Plugin\BlogPosts\Model\Post;

/**
 * 記事作成イベントクラス。
 * 新規記事が作成された際に発行されるドメインイベント。
 * 他のプラグインやリスナーはこのイベントを購読して処理を追加できる。
 */
class PostCreated
{
    /**
     * PostCreated イベントを生成する。
     *
     * @param Post $post 作成された記事エンティティ
     */
    public function __construct(public readonly Post $post) {}
}
