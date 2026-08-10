<?php

namespace TightPress\Core\Database\Migration;

/**
 * マイグレーションのインターフェース。
 *
 * 全てのマイグレーションクラスがこのインターフェースを実装する。
 * up() でスキーマを適用し、down() でロールバックする。
 */
interface MigrationInterface
{
    /**
     * スキーマのアップ処理を実行する（テーブル作成など）。
     *
     * @return void
     */
    public function up(): void;

    /**
     * スキーマのダウン処理を実行する（テーブル削除など）。
     *
     * @return void
     */
    public function down(): void;
}
