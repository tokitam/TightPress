<?php

namespace TightPress\Core\Plugin;

/**
 * プラグインのメタ情報を保持する値オブジェクト。
 *
 * イミュータブルな設計とするため全プロパティは readonly とし、
 * コンストラクタインジェクションでのみ値を設定できる。
 */
final class PluginMeta
{
    /**
     * PluginMeta を生成する。
     *
     * @param string $id          プラグインの一意な識別子（例: 'blog-posts'）
     * @param string $name        プラグインの表示名（例: 'Blog Posts'）
     * @param string $version     セマンティックバージョン文字列（例: '1.0.0'）
     * @param string $description プラグインの説明文
     * @param string $author      プラグイン作成者の名前または組織名
     * @param string $entryFile   plugin.php の絶対パス
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $version,
        public readonly string $description,
        public readonly string $author,
        public readonly string $entryFile,
    ) {}
}
