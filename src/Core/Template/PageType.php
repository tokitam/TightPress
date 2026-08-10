<?php

declare(strict_types=1);

namespace TightPress\Core\Template;

/**
 * 現在のリクエストページ種別を表す列挙型
 *
 * テンプレート解決時にどのページを表示しているかを識別するために使用する。
 */
enum PageType
{
    /** 記事一覧（ホーム） */
    case Home;

    /** 記事詳細 */
    case Single;

    /** 固定ページ */
    case Page;

    /** 404 */
    case NotFound;
}
