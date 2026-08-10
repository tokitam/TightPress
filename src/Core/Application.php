<?php

namespace TightPress\Core;

/**
 * DIコンテナ（アプリケーション本体）。
 *
 * サービスのバインドと解決を担うシンプルなコンテナ。
 * bind() で毎回新規生成、singleton() で初回のみ生成してキャッシュする。
 */
class Application
{
    /**
     * バインディング（抽象名 → ファクトリクロージャ）のマップ。
     *
     * @var array<string, callable>
     */
    private array $bindings = [];

    /**
     * シングルトンインスタンスのキャッシュ。
     *
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * グローバルアクセス用の静的インスタンス（app() ヘルパーから参照される）。
     *
     * @var static|null
     */
    private static ?self $instance = null;

    /**
     * サービスをバインドする（resolve のたびに新規生成される）。
     *
     * @param  string   $abstract インターフェース名または識別子
     * @param  callable $factory  Application を受け取りインスタンスを返すファクトリ
     * @return void
     */
    public function bind(string $abstract, callable $factory): void
    {
        // バインディングに登録する（シングルトンキャッシュには入れない）
        $this->bindings[$abstract] = $factory;
    }

    /**
     * サービスをシングルトンとしてバインドする（初回のみ生成・以降はキャッシュを返す）。
     *
     * @param  string   $abstract インターフェース名または識別子
     * @param  callable $factory  Application を受け取りインスタンスを返すファクトリ
     * @return void
     */
    public function singleton(string $abstract, callable $factory): void
    {
        // シングルトン用フラグとしてバインディングに登録する。
        // make() の中でキャッシュ済みかを確認して初回だけファクトリを呼び出す。
        $this->bindings[$abstract] = $factory;

        // シングルトンとして登録したことをメタ情報として保持する
        $this->singletons[$abstract] = true;
    }

    /**
     * シングルトン登録済みの識別子を追跡するフラグ。
     *
     * @var array<string, bool>
     */
    private array $singletons = [];

    /**
     * サービスを解決して返す。
     *
     * 解決の優先順位:
     * 1. シングルトンキャッシュに存在すればキャッシュを返す
     * 2. バインディングがあればファクトリを呼び出す
     * 3. いずれもなければ new $abstract() でオートワイヤリングを試みる
     *
     * @template T
     * @param  class-string<T>|string $abstract インターフェース名または識別子
     * @return T|mixed
     * @throws \RuntimeException バインディングが見つからず new も失敗した場合
     */
    public function make(string $abstract): mixed
    {
        // シングルトンキャッシュに存在する場合はそのまま返す
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // バインディングが登録されている場合はファクトリを呼び出す
        if (isset($this->bindings[$abstract])) {
            $factory  = $this->bindings[$abstract];
            $instance = $factory($this);

            // シングルトン登録済みの場合はキャッシュに保存する
            if (isset($this->singletons[$abstract])) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        }

        // バインディングがない場合は new で直接インスタンス化を試みる（オートワイヤリング）
        if (class_exists($abstract)) {
            return new $abstract();
        }

        throw new \RuntimeException(
            "サービスのバインディングが見つかりません: {$abstract}"
        );
    }

    /**
     * グローバルインスタンスを登録する（app() ヘルパーが使用する）。
     *
     * @param  self $app 登録するアプリケーションインスタンス
     * @return void
     */
    public static function setInstance(self $app): void
    {
        self::$instance = $app;
    }

    /**
     * グローバルインスタンスを返す。
     *
     * @return static
     * @throws \RuntimeException setInstance() が呼ばれていない場合
     */
    public static function getInstance(): static
    {
        if (self::$instance === null) {
            throw new \RuntimeException(
                'アプリケーションインスタンスが未登録です。先に Application::setInstance() を呼び出してください。'
            );
        }

        return self::$instance;
    }
}
