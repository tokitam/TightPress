<?php

namespace TightPress\Core\Database;

use TightPress\Core\Database\Drivers\MySQLDriver;
use TightPress\Core\Database\Drivers\PostgreSQLDriver;
use TightPress\Core\Database\Drivers\SQLiteDriver;

/**
 * データベース接続ファクトリ。
 *
 * 設定配列の 'driver' キーを見て適切なドライバーインスタンスを生成する。
 * アプリケーションのブートストラップで使われる静的ファクトリメソッドを提供する。
 */
class Connection
{
    /**
     * 設定配列からドライバーインスタンスを生成して返す。
     *
     * @param  array<string, mixed> $config config/database.php の内容
     * @return ConnectionInterface
     * @throws \InvalidArgumentException 未対応ドライバーを指定した場合
     */
    public static function create(array $config): ConnectionInterface
    {
        // 'driver' キーが未設定の場合は SQLite をデフォルトとして使用する
        $driver = $config['driver'] ?? 'sqlite';

        // ドライバー名に対応するクラスをインスタンス化して返す
        return match ($driver) {
            'sqlite' => new SQLiteDriver($config['sqlite']),
            'mysql'  => new MySQLDriver($config['mysql']),
            'pgsql'  => new PostgreSQLDriver($config['pgsql']),
            default  => throw new \InvalidArgumentException(
                "未対応のドライバーが指定されました: {$driver}"
            ),
        };
    }
}
