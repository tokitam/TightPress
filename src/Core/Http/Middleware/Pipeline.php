<?php

declare(strict_types=1);

namespace TightPress\Core\Http\Middleware;

use TightPress\Core\Http\Request;
use TightPress\Core\Http\Response;

/**
 * ミドルウェアパイプラインクラス。
 *
 * 複数のミドルウェアをチェーンして順番に実行するためのクラス。
 * Phase 1 ではスタブ実装であり、ミドルウェアが登録されていない場合は
 * 直接 $handler を呼び出す。
 * Phase 2 以降でミドルウェアの実際の連鎖処理を実装予定。
 */
class Pipeline
{
    /**
     * 登録されたミドルウェアのリスト。
     *
     * @var list<MiddlewareInterface>
     */
    private array $middleware = [];

    /**
     * ミドルウェアをパイプラインに追加する。
     *
     * メソッドチェーンで複数のミドルウェアを追加できる。
     * Phase 1 では追加は可能だが、run() 時には実際には使用しない（スタブ）。
     *
     * @param MiddlewareInterface $middleware 追加するミドルウェアインスタンス
     * @return static メソッドチェーン用に自身を返す
     */
    public function pipe(MiddlewareInterface $middleware): static
    {
        // ミドルウェアをリストに追加する（Phase 2 で実際に使用する）
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * リクエストをパイプラインに通してレスポンスを返す。
     *
     * Phase 1 のスタブ実装:
     *   - 登録ミドルウェアがない場合は $handler を直接呼ぶ。
     *   - 登録ミドルウェアがある場合も、Phase 1 では直接 $handler を呼ぶ（TODO）。
     *
     * Phase 2 以降では、登録されたミドルウェアを先頭から順番に実行し、
     * 最後に $handler を呼ぶ連鎖処理を実装する。
     *
     * @param Request  $request 処理するリクエストインスタンス
     * @param callable $handler 最終ハンドラ。シグネチャ: (Request): Response
     * @return Response 処理結果のレスポンスインスタンス
     */
    public function run(Request $request, callable $handler): Response
    {
        // TODO: Phase 2 でミドルウェアの連鎖処理を実装する
        // Phase 1 では登録ミドルウェアの有無に関わらず直接ハンドラを呼ぶ
        return $handler($request);
    }
}
