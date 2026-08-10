<?php

declare(strict_types=1);

namespace TightPress\Core\Template;

/**
 * テンプレートファイルの解決と描画を担当するエンジンクラス
 *
 * WordPress のテンプレート階層に相当するファイル候補から
 * 実際に存在するものを選択し、PHP テンプレートとして描画して
 * HTML 文字列を返す。
 */
class TemplateEngine
{
    /**
     * @param ThemeLoader $themeLoader テーマディレクトリ管理クラス
     */
    public function __construct(
        private readonly ThemeLoader $themeLoader
    ) {}

    /**
     * 候補ファイルリストから最初に存在するテンプレートの絶対パスを返す
     *
     * 空文字はスキップする（TemplateContext のカスタムテンプレートが未設定の場合の対応）。
     * 優先度の高いものから順に candidates に渡すことで、
     * WordPress のテンプレート階層と同等の解決順序を実現できる。
     *
     * @param  string[] $candidates  ファイル名の優先リスト（例: ['single-post.php', 'single.php', 'index.php']）
     * @return string                見つかったファイルの絶対パス
     * @throws \RuntimeException     1件も見つからない場合
     */
    public function resolve(array $candidates): string
    {
        // テーマルートのパスを取得する
        $themeDir = $this->themeLoader->getThemeDir();

        foreach ($candidates as $file) {
            // 空文字はスキップする（カスタムテンプレートが未設定の場合を想定）
            if ($file === '') {
                continue;
            }

            // テーマディレクトリとファイル名を結合して絶対パスを生成する
            $path = $themeDir . '/' . $file;

            // ファイルが存在すればそのパスを返す
            if (file_exists($path)) {
                return $path;
            }
        }

        // 有効な候補が1件も見つからなかった場合はエラーを投げる
        // フォールバック: 最低限 index.php は存在するはずだが念のためエラー
        throw new \RuntimeException(
            'テンプレートファイルが見つかりませんでした。候補: ' . implode(', ', array_filter($candidates))
        );
    }

    /**
     * テンプレートを描画して HTML 文字列を返す
     *
     * 出力バッファリングを使用してテンプレートの echo/print 出力をキャプチャし、
     * 文字列として返す。テンプレート内でエラーが発生した場合はバッファを破棄して
     * 例外を再スローする。
     *
     * @param string          $templatePath  resolve() で得たテンプレートの絶対パス
     * @param TemplateContext  $context       テンプレートに渡すコンテキスト
     * @return string                         描画された HTML
     * @throws \RuntimeException              テンプレートが存在しない場合
     * @throws \Throwable                     テンプレート内で例外・エラーが発生した場合
     */
    public function render(string $templatePath, TemplateContext $context): string
    {
        // テンプレートファイルの存在を確認する
        if (!file_exists($templatePath)) {
            throw new \RuntimeException("テンプレートが見つかりません: {$templatePath}");
        }

        // TemplateContext の内容をローカル変数に展開する（既存変数は上書きしない）
        extract($context->toArray(), EXTR_SKIP);

        // 出力バッファリングを開始してテンプレートの出力をキャプチャする
        ob_start();
        try {
            // テンプレートファイルを読み込んで実行する
            require $templatePath;

            // バッファの内容を文字列として返す
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            // テンプレート内でエラーが発生した場合はバッファを破棄して例外を再スローする
            ob_end_clean();
            throw $e;
        }
    }
}
