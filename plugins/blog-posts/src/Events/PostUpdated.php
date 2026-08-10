<?php

namespace TightPress\Plugin\BlogPosts\Events;

use TightPress\Plugin\BlogPosts\Model\Post;

/**
 * 記事更新イベントクラス。
 * 既存記事が更新された際に発行されるドメインイベント。
 * 他のプラグインやリスナーはこのイベントを購読して処理を追加できる。
 */
class PostUpdated
{
    /**
     * PostUpdated イベントを生成する。
     *
     * @param Post $post 更新後の記事エンティティ
     */
    public function __construct(public readonly Post $post) {}
}
