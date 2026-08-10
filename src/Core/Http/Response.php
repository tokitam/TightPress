<?php

declare(strict_types=1);

namespace TightPress\Core\Http;

/**
 * HTTPレスポンスを表すイミュータブルなクラス。
 *
 * withHeader / withStatus / withBody は新しいインスタンスを返すため、
 * 元のインスタンスは変更されない（イミュータブル設計）。
 * メソッドチェーンで組み立てて最後に send() を呼ぶ使い方を想定している。
 */
class Response
{
    /**
     * コンストラクタ。
     *
     * @param string $body    レスポンスボディ
     * @param int    $status  HTTPステータスコード
     * @param array  $headers レスポンスヘッダー連想配列
     */
    public function __construct(
        private string $body    = '',
        private int    $status  = 200,
        private array  $headers = ['Content-Type' => 'text/html; charset=UTF-8'],
    ) {}

    /**
     * ヘッダーを追加または上書きした新しいインスタンスを返す。
     *
     * 元のインスタンスは変更されない（イミュータブル）。
     *
     * @param string $name  ヘッダー名（例: 'Content-Type'）
     * @param string $value ヘッダー値（例: 'application/json'）
     * @return static 新しいインスタンス
     */
    public function withHeader(string $name, string $value): static
    {
        // クローンを作成して変更を加えることでイミュータブル性を保つ
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * ステータスコードを変更した新しいインスタンスを返す。
     *
     * 元のインスタンスは変更されない（イミュータブル）。
     *
     * @param int $code HTTPステータスコード（例: 200, 302, 404）
     * @return static 新しいインスタンス
     */
    public function withStatus(int $code): static
    {
        // クローンを作成してステータスコードのみ更新する
        $clone = clone $this;
        $clone->status = $code;
        return $clone;
    }

    /**
     * ボディを設定した新しいインスタンスを返す。
     *
     * 元のインスタンスは変更されない（イミュータブル）。
     *
     * @param string $body レスポンスボディ文字列
     * @return static 新しいインスタンス
     */
    public function withBody(string $body): static
    {
        // クローンを作成してボディのみ更新する
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    /**
     * HTTPヘッダーを送信してボディを出力する。
     *
     * この関数を呼んだ後にヘッダーを追加・変更することはできない点に注意。
     * 出力バッファリングを使用している場合は ob_end_flush() との順序に気をつけること。
     *
     * @return void
     */
    public function send(): void
    {
        // ステータスコードを設定する
        http_response_code($this->status);

        // 各ヘッダーを送信する
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // レスポンスボディを出力する
        echo $this->body;
    }

    /**
     * 現在のステータスコードを返す。
     *
     * @return int HTTPステータスコード
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * 現在のボディを返す。
     *
     * @return string レスポンスボディ
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * 現在のヘッダー配列を返す。
     *
     * @return array ヘッダー連想配列
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * HTML レスポンスを生成するショートカット。
     *
     * @param string $body   HTMLボディ文字列
     * @param int    $status HTTPステータスコード（デフォルト: 200）
     * @return static 生成したレスポンスインスタンス
     */
    public static function html(string $body, int $status = 200): static
    {
        return new static($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * リダイレクトレスポンスを生成するショートカット。
     *
     * @param string $url    リダイレクト先URL
     * @param int    $status HTTPステータスコード（デフォルト: 302）
     * @return static 生成したレスポンスインスタンス
     */
    public static function redirect(string $url, int $status = 302): static
    {
        return new static('', $status, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Location'     => $url,
        ]);
    }

    /**
     * 404 Not Found レスポンスを生成するショートカット。
     *
     * @return static ステータス 404 のレスポンスインスタンス
     */
    public static function notFound(): static
    {
        return new static('<h1>404 Not Found</h1>', 404);
    }

    /**
     * JSON レスポンスを生成するショートカット。
     *
     * @param mixed $data    JSON にエンコードするデータ
     * @param int   $status  HTTPステータスコード（デフォルト: 200）
     * @return static 生成したレスポンスインスタンス
     */
    public static function json(mixed $data, int $status = 200): static
    {
        // データを JSON 文字列にエンコードする（Unicode エスケープなし）
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return new static($body ?: '', $status, ['Content-Type' => 'application/json; charset=UTF-8']);
    }
}
