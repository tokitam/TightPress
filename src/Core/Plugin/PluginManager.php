<?php

namespace TightPress\Core\Plugin;

use TightPress\Core\Application;
use InvalidArgumentException;
use RuntimeException;

/**
 * プラグインの登録・起動・インストール・アンインストールを管理するクラス。
 *
 * plugins/ ディレクトリを走査してプラグインを自動ロードし、
 * 全プラグインのライフサイクルメソッド（register / boot / install / uninstall）を
 * 一括して呼び出す役割を担う。
 */
class PluginManager
{
    /**
     * 登録済みプラグインの連想配列。
     *
     * キーはプラグイン ID、値は PluginInterface インスタンス。
     *
     * @var array<string, PluginInterface>
     */
    private array $plugins = [];

    /**
     * 指定ディレクトリ以下のプラグインをロードする。
     *
     * サブディレクトリを走査し、各ディレクトリ内の plugin.php を require する。
     * plugin.php は PluginInterface を実装したインスタンスを返す必要がある。
     *
     * @param string $pluginsDir プラグインが格納されたディレクトリの絶対パス
     * @return void
     * @throws RuntimeException plugin.php の戻り値が PluginInterface を実装していない場合
     */
    public function loadFromDirectory(string $pluginsDir): void
    {
        // ディレクトリが存在しない場合は何もせず終了する
        if (! is_dir($pluginsDir)) {
            return;
        }

        // プラグインディレクトリ直下のサブディレクトリを走査する
        $iterator = new \DirectoryIterator($pluginsDir);
        foreach ($iterator as $entry) {
            // . と .. および通常ファイルはスキップする
            if ($entry->isDot() || ! $entry->isDir()) {
                continue;
            }

            // 各プラグインのエントリポイント（plugin.php）のパスを組み立てる
            $entryFile = $entry->getPathname() . '/plugin.php';

            // plugin.php が存在しないプラグインディレクトリはスキップする
            if (! file_exists($entryFile)) {
                continue;
            }

            // plugin.php を読み込んでプラグインインスタンスを取得する
            $plugin = require $entryFile;

            // 戻り値が PluginInterface を実装しているか検証する
            if (! $plugin instanceof PluginInterface) {
                throw new RuntimeException(
                    sprintf(
                        'プラグイン "%s" の plugin.php は PluginInterface を実装したインスタンスを返す必要があります。',
                        $entry->getFilename()
                    )
                );
            }

            // プラグインを内部リストに追加する
            $this->plugins[$plugin->meta()->id] = $plugin;
        }
    }

    /**
     * プラグインを内部リストに登録する。
     *
     * loadFromDirectory() を使わず手動でプラグインを登録したい場合に使用する。
     *
     * @param PluginInterface $plugin 登録するプラグインインスタンス
     * @return void
     */
    public function register(PluginInterface $plugin): void
    {
        // プラグイン ID をキーとして登録する
        $this->plugins[$plugin->meta()->id] = $plugin;
    }

    /**
     * 登録済み全プラグインの register() を呼び出す。
     *
     * DI コンテナへのサービスバインドを行うフェーズ。
     * boot() より前に必ず実行する。
     *
     * @param Application $app DI コンテナ（アプリケーション本体）
     * @return void
     */
    public function registerAll(Application $app): void
    {
        // 全プラグインのサービス登録を順番に実行する
        foreach ($this->plugins as $plugin) {
            $plugin->register($app);
        }
    }

    /**
     * 登録済み全プラグインの boot() を呼び出す。
     *
     * ルート・イベントリスナーなどの登録を行うフェーズ。
     * registerAll() の後に実行する。
     *
     * @param Application $app DI コンテナ（アプリケーション本体）
     * @return void
     */
    public function bootAll(Application $app): void
    {
        // 全プラグインの起動処理を順番に実行する
        foreach ($this->plugins as $plugin) {
            $plugin->boot($app);
        }
    }

    /**
     * 指定 ID のプラグインのインストール処理を実行する。
     *
     * プラグインが存在しない場合は InvalidArgumentException をスローする。
     *
     * @param string      $pluginId インストール対象のプラグイン ID
     * @param Application $app      DI コンテナ（アプリケーション本体）
     * @return void
     * @throws InvalidArgumentException 指定 ID のプラグインが登録されていない場合
     */
    public function install(string $pluginId, Application $app): void
    {
        // 対象プラグインが登録済みか確認する
        $plugin = $this->findOrFail($pluginId);

        // プラグインのインストール処理（テーブル作成など）を実行する
        $plugin->install($app);
    }

    /**
     * 指定 ID のプラグインのアンインストール処理を実行する。
     *
     * プラグインが存在しない場合は InvalidArgumentException をスローする。
     *
     * @param string      $pluginId アンインストール対象のプラグイン ID
     * @param Application $app      DI コンテナ（アプリケーション本体）
     * @return void
     * @throws InvalidArgumentException 指定 ID のプラグインが登録されていない場合
     */
    public function uninstall(string $pluginId, Application $app): void
    {
        // 対象プラグインが登録済みか確認する
        $plugin = $this->findOrFail($pluginId);

        // プラグインのアンインストール処理（テーブル削除など）を実行する
        $plugin->uninstall($app);
    }

    /**
     * 登録済みプラグインの一覧を返す。
     *
     * @return array<string, PluginInterface> プラグイン ID をキーとした連想配列
     */
    public function getActive(): array
    {
        return $this->plugins;
    }

    /**
     * 指定 ID のプラグインを取得する。
     *
     * 存在しない場合は InvalidArgumentException をスローする。
     *
     * @param string $pluginId 取得対象のプラグイン ID
     * @return PluginInterface 対応するプラグインインスタンス
     * @throws InvalidArgumentException 指定 ID のプラグインが登録されていない場合
     */
    private function findOrFail(string $pluginId): PluginInterface
    {
        // 指定 ID のプラグインが登録されているか確認する
        if (! isset($this->plugins[$pluginId])) {
            throw new InvalidArgumentException(
                sprintf('プラグイン ID "%s" は登録されていません。', $pluginId)
            );
        }

        return $this->plugins[$pluginId];
    }
}
