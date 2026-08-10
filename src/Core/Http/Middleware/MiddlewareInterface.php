<?php

declare(strict_types=1);

namespace TightPress\Core\Http\Middleware;

use TightPress\Core\Http\Request;
use TightPress\Core\Http\Response;

/**
 * ミドルウェアのインターフェース。
 *
 * リクエスト処理の前後に横断的な処理（認証・ログ・キャッシュなど）を
 * 差し込むための統一インターフェース。
 * Phase 1 ではスタブとして定義のみ行い、Phase 2 以降で実装クラスを追加する。
 */
interface MiddlewareInterface
{
    /**
     * リクエストを処理して次のミドルウェアまたはハンドラに渡す。
     *
     * $next を呼ばずに Response を返すことで、後続の処理を短絡（ショートサーキット）できる。
     * 例: 認証ミドルウェアが未認証を検知した場合に即 401 を返すなど。
     *
     * @param Request  $request 現在のリクエストインスタンス
     * @param callable $next    次のミドルウェアまたは最終ハンドラ。シグネチャ: (Request): Response
     * @return Response 処理結果のレスポンスインスタンス
     */
    public function handle(Request $request, callable $next): Response;
}
