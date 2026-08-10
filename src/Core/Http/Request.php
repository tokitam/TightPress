<?php

declare(strict_types=1);

namespace TightPress\Core\Http;

/**
 * HTTPリクエストをラップするクラス。
 *
 * グローバル変数（$_SERVER, $_GET, $_POST, $_COOKIE, $_FILES）を
 * 読み取り専用プロパティとして保持する。
 * PSR-7 には依存せず、シンプルな値オブジェクトとして機能する。
 */
class Request
{
    /** HTTPメソッド（'GET' | 'POST' | 'PUT' | 'DELETE' など、大文字） */
    public readonly string $method;

    /** リクエストURI（クエリ文字列を含む。例: '/posts/hello-world?foo=bar'） */
    public readonly string $uri;

    /** パス部分のみ（クエリなし。例: '/posts/hello-world'） */
    public readonly string $path;

    /** クエリパラメーター（$_GET の内容） */
    public readonly array $query;

    /** リクエストボディ（$_POST または JSON デコード結果） */
    public readonly array $input;

    /** 正規化されたHTTPヘッダー連想配列（例: ['Content-Type' => 'application/json']） */
    public readonly array $headers;

    /** クッキー（$_COOKIE の内容） */
    public readonly array $cookies;

    /** アップロードファイル（$_FILES の内容） */
    public readonly array $files;

    /**
     * グローバル変数からリクエストインスタンスを生成する。
     *
     * Content-Type が application/json のとき、php://input を JSON デコードして
     * $input に格納する。それ以外は $_POST を使用する。
     *
     * @return self グローバル変数から生成したリクエストインスタンス
     */
    public static function fromGlobals(): self
    {
        // HTTPメソッドを取得する
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // リクエストURIをデコードする（マルチバイト文字を含む場合も考慮）
        $uri = rawurldecode($_SERVER['REQUEST_URI'] ?? '/');

        // URIからパス部分のみを取り出す
        $path = (string) parse_url($uri, PHP_URL_PATH);

        // クエリパラメーターは $_GET をそのまま使用する
        $query = $_GET;

        // Content-Type を確認して入力値の取得方法を決定する
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            // JSON ボディを php://input から読み取ってデコードする
            $rawBody = file_get_contents('php://input');
            $decoded = json_decode($rawBody ?: '', true);
            // デコード失敗時は空配列にフォールバックする
            $input = is_array($decoded) ? $decoded : [];
        } else {
            // 通常のフォーム送信は $_POST を使用する
            $input = $_POST;
        }

        // $_SERVER の HTTP_* キーからヘッダーを抽出・正規化する
        $headers = self::extractHeaders($_SERVER);

        // クッキーは $_COOKIE をそのまま使用する
        $cookies = $_COOKIE;

        // アップロードファイルは $_FILES をそのまま使用する
        $files = $_FILES;

        return new self($method, $uri, $path, $query, $input, $headers, $cookies, $files);
    }

    /**
     * テストなど任意の値からリクエストインスタンスを生成するファクトリメソッド。
     *
     * @param string $method  HTTPメソッド
     * @param string $uri     リクエストURI
     * @param array  $query   クエリパラメーター
     * @param array  $input   リクエストボディ
     * @param array  $headers HTTPヘッダー
     * @param array  $cookies クッキー
     * @param array  $files   アップロードファイル
     * @return self 生成されたリクエストインスタンス
     */
    public static function create(
        string $method,
        string $uri,
        array  $query   = [],
        array  $input   = [],
        array  $headers = [],
        array  $cookies = [],
        array  $files   = [],
    ): self {
        // URIからパス部分を取り出す
        $path = (string) parse_url($uri, PHP_URL_PATH);

        return new self($method, $uri, $path, $query, $input, $headers, $cookies, $files);
    }

    /**
     * コンストラクタ。fromGlobals() または create() からのみ呼び出す。
     *
     * @param string $method  HTTPメソッド
     * @param string $uri     リクエストURI
     * @param string $path    パス部分（クエリなし）
     * @param array  $query   クエリパラメーター
     * @param array  $input   リクエストボディ
     * @param array  $headers HTTPヘッダー
     * @param array  $cookies クッキー
     * @param array  $files   アップロードファイル
     */
    private function __construct(
        string $method,
        string $uri,
        string $path,
        array  $query,
        array  $input,
        array  $headers,
        array  $cookies,
        array  $files,
    ) {
        // メソッドは常に大文字で保持する
        $this->method  = strtoupper($method);
        $this->uri     = $uri;
        // パスが空文字のときは '/' にフォールバックする
        $this->path    = $path === '' ? '/' : $path;
        $this->query   = $query;
        $this->input   = $input;
        $this->headers = $headers;
        $this->cookies = $cookies;
        $this->files   = $files;
    }

    /**
     * $_SERVER の HTTP_* キーを 'Content-Type' 形式のヘッダー名に変換して返す。
     *
     * 変換例: HTTP_CONTENT_TYPE → Content-Type
     *         HTTP_ACCEPT_ENCODING → Accept-Encoding
     *
     * また、CONTENT_TYPE キーも Content-Type として取り込む
     * （一部の環境では HTTP_ プレフィックスなしで設定されるため）。
     *
     * @param array $server $_SERVER 相当の配列
     * @return array 正規化されたヘッダー連想配列
     */
    private static function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            // HTTP_ プレフィックスで始まるキーのみ処理する
            if (str_starts_with($key, 'HTTP_')) {
                // プレフィックスを取り除いてヘッダー名に変換する
                // 例: HTTP_ACCEPT_ENCODING → Accept-Encoding
                $header = str_replace('_', '-', substr($key, 5));
                $header = implode('-', array_map('ucfirst', explode('-', strtolower($header))));
                $headers[$header] = $value;
            }
        }

        // CONTENT_TYPE は HTTP_CONTENT_TYPE とは別キーで設定される環境もある
        if (isset($server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $server['CONTENT_TYPE'];
        }

        return $headers;
    }
}
