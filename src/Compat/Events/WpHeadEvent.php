<?php

declare(strict_types=1);

namespace TightPress\Compat\Events;

/**
 * wp_head アクションに対応するイベント
 *
 * WordPress の wp_head フックが実行されたタイミングで dispatch される。
 * ヘッダー部分への出力（メタタグ・スタイルシート等）を処理するリスナーが購読する。
 */
class WpHeadEvent {}
