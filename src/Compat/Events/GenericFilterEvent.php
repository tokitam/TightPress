<?php

declare(strict_types=1);

namespace TightPress\Compat\Events;

/**
 * マッピングされていないフィルターフック用の汎用イベント
 *
 * HookBridge に専用イベントクラスが登録されていないフック名に対して
 * apply_filters('some_filter', $value) が呼ばれた際に生成される。
 * リスナーは $event->value を直接書き換えることでフィルターを適用する。
 * フィルター適用後、HookBridge::extractValue() で変更後の値を取り出す。
 */
class GenericFilterEvent
{
    /**
     * コンストラクタ
     *
     * @param string $hook  フック名（例: 'the_title', 'the_content'）
     * @param mixed  $value フィルター対象の値（リスナーが書き換え可能）
     * @param array  $args  apply_filters に渡された追加引数
     */
    public function __construct(
        public readonly string $hook,
        public mixed           $value,   // readonly ではない（リスナーが書き換える）
        public readonly array  $args = [],
    ) {}
}
