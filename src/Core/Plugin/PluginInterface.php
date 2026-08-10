<?php

namespace TightPress\Core\Plugin;

use TightPress\Core\Application;

/**
 * プラグインが実装すべきインターフェース。
 *
 * 全プラグインはこのインターフェースを implements し、
 * メタ情報の提供・サービス登録・起動・インストール・アンインストールの各処理を実装する。
 */
interface PluginInterface
{
    /**
     * プラグインのメタ情報を返す。
     *
     * @return PluginMeta プラグインのメタ情報値オブジェクト
     */
    public function meta(): PluginMeta;

    /**
     * プラグインが依存するサービスを DI コンテナに登録する。
     *
     * boot() より前に呼ばれる。
     * サービスのバインドや設定の読み込みはここで行う。
     *
     * @param Application $app DI コンテナ（アプリケーション本体）
     * @return void
     */
    public function register(Application $app): void;

    /**
     * プラグインを起動する。
     *
     * ルート・イベントリスナー・管理画面メニューなどを登録する。
     * register() の後に呼ばれるため、他プラグインのサービスも利用できる。
     *
     * @param Application $app DI コンテナ（アプリケーション本体）
     * @return void
     */
    public function boot(Application $app): void;

    /**
     * プラグインのインストール処理を実行する。
     *
     * データベーステーブルの作成など、初回有効化時にのみ必要な処理を行う。
     *
     * @param Application $app DI コンテナ（アプリケーション本体）
     * @return void
     */
    public function install(Application $app): void;

    /**
     * プラグインのアンインストール処理を実行する。
     *
     * データベーステーブルの削除やオプション値の消去など、
     * プラグイン削除時に行う後処理を実装する。
     *
     * @param Application $app DI コンテナ（アプリケーション本体）
     * @return void
     */
    public function uninstall(Application $app): void;
}
