<?php

declare(strict_types=1);

namespace TightPress\Compat\Events;

/**
 * マッピングされていないアクションフック用の汎用イベント
 *
 * HookBridge に専用イベントクラスが登録されていないフック名に対して
 * do_action('some_unmapped_hook') が呼ばれた際に生成される。
 * フック名と追加引数を保持する。
 */
class GenericActionEvent
{
    /**
     * コンストラクタ
     *
     * @param string $hook フック名（例: 'init', 'after_setup_theme'）
     * @param array  $args do_action に渡された追加引数
     */
    public function __construct(
        public readonly string $hook,
        public readonly array  $args = [],
    ) {}
}
