<?php

declare(strict_types=1);

namespace TightPress\Core\Http;

/**
 * URLパターンベースのルータークラス。
 *
 * '/posts/{slug}' のようなプレースホルダー構文でルートを登録し、
 * dispatch() でリクエストにマッチするハンドラを呼び出す。
 * PSR-15 には依存せず、シンプルなコールバックベースで動作する。
 */
class Router
{
    /**
     * 登録されたルートのリスト。
     *
     * 各要素は以下のキーを持つ連想配列:
     *   method:  HTTPメソッド（大文字）
     *   pattern: 元のURLパターン文字列（例: '/posts/{slug}'）
     *   regex:   マッチング用の正規表現文字列
     *   params:  プレースホルダー名のリスト（例: ['slug']）
     *   handler: ルートハンドラのコールバック
     *
     * @var list<array{method: string, pattern: string, regex: string, params: list<string>, handler: callable}>
     */
    private array $routes = [];

    /**
     * GET ルートを登録する。
     *
     * @param string   $pattern URLパターン（例: '/posts/{slug}'）
     * @param callable $handler ハンドラ。シグネチャ: (Request $req, array $params): Response
     * @return void
     */
    public function get(string $pattern, callable $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    /**
     * POST ルートを登録する。
     *
     * @param string   $pattern URLパターン（例: '/posts/create'）
     * @param callable $handler ハンドラ。シグネチャ: (Request $req, array $params): Response
     * @return void
     */
    public function post(string $pattern, callable $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    /**
     * PUT ルートを登録する。
     *
     * @param string   $pattern URLパターン（例: '/posts/{id}'）
     * @param callable $handler ハンドラ。シグネチャ: (Request $req, array $params): Response
     * @return void
     */
    public function put(string $pattern, callable $handler): void
    {
        $this->addRoute('PUT', $pattern, $handler);
    }

    /**
     * DELETE ルートを登録する。
     *
     * @param string   $pattern URLパターン（例: '/posts/{id}'）
     * @param callable $handler ハンドラ。シグネチャ: (Request $req, array $params): Response
     * @return void
     */
    public function delete(string $pattern, callable $handler): void
    {
        $this->addRoute('DELETE', $pattern, $handler);
    }

    /**
     * リクエストにマッチするルートを探してハンドラを実行する。
     *
     * メソッドとパスの両方がマッチする最初のルートを使用する。
     * マッチするルートが見つからない場合は 404 Response を返す。
     *
     * @param Request $request 処理するリクエストインスタンス
     * @return Response ハンドラが返したレスポンス、またはマッチなし時は 404 レスポンス
     */
    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            // まずHTTPメソッドが一致するか確認する
            if ($route['method'] !== $request->method) {
                continue;
            }

            // リクエストパスが正規表現にマッチするか確認する
            if (preg_match($route['regex'], $request->path, $matches)) {
                // プレースホルダー名と実際の値を組み合わせて連想配列を作る
                $params = [];
                foreach ($route['params'] as $name) {
                    $params[$name] = $matches[$name];
                }

                // ハンドラを呼び出してレスポンスを返す
                return ($route['handler'])($request, $params);
            }
        }

        // マッチするルートが見つからなかった場合は 404 を返す
        return Response::notFound();
    }

    /**
     * 登録済みのルートをすべて取得する（主にテスト・デバッグ用）。
     *
     * @return list<array{method: string, pattern: string, regex: string, params: list<string>, handler: callable}> ルートのリスト
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * ルートを内部リストに追加してURLパターンを正規表現に変換する。
     *
     * '{slug}' のようなプレースホルダーを named capture group に変換する。
     * 例: '/posts/{slug}' → '#^/posts/(?P<slug>[^/]+)$#u'
     *
     * @param string   $method  HTTPメソッド（大文字に正規化される）
     * @param string   $pattern URLパターン
     * @param callable $handler ルートハンドラ
     * @return void
     */
    private function addRoute(string $method, string $pattern, callable $handler): void
    {
        // プレースホルダーを正規表現のnamed groupに変換しながら名前を収集する
        $paramNames = [];
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            function (array $m) use (&$paramNames): string {
                // パラメーター名を記録する
                $paramNames[] = $m[1];
                // '[^/]+' でスラッシュを含まない1文字以上にマッチする named group を返す
                return '(?P<' . $m[1] . '>[^/]+)';
            },
            $pattern
        );

        // 正規表現を先頭と末尾のアンカーで囲む（u フラグでマルチバイト対応）
        $regex = '#^' . $regex . '$#u';

        // ルートを内部配列に追加する
        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => $pattern,
            'regex'   => $regex,
            'params'  => $paramNames,
            'handler' => $handler,
        ];
    }
}
