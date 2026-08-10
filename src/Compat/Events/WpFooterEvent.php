<?php

declare(strict_types=1);

namespace TightPress\Compat\Events;

/**
 * wp_footer アクションに対応するイベント
 *
 * WordPress の wp_footer フックが実行されたタイミングで dispatch される。
 * フッター部分への出力（スクリプト・アナリティクスコード等）を処理するリスナーが購読する。
 */
class WpFooterEvent {}
