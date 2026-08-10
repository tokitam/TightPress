<?php

declare(strict_types=1);

namespace TightPress\Compat;

/**
 * スタイルとスクリプトのキュー管理クラス。
 *
 * wp_enqueue_style() / wp_enqueue_script() 互換のキュー機能を提供する。
 * HTML 出力時に printStyles() / printScripts() でタグを生成する。
 */
class AssetQueue
{
    /**
     * スタイルのキュー。
     *
     * ハンドル名をキーとし、src・deps・ver・media を保持する。
     *
     * @var array<string, array{src: string, deps: list<string>, ver: ?string, media: string}>
     */
    private array $styles = [];

    /**
     * スクリプトのキュー。
     *
     * ハンドル名をキーとし、src・deps・ver・inFooter を保持する。
     *
     * @var array<string, array{src: string, deps: list<string>, ver: ?string, inFooter: bool}>
     */
    private array $scripts = [];

    /**
     * スタイルをキューに追加する。
     *
     * @param string        $handle スタイルの識別ハンドル名
     * @param string        $src    スタイルシートの URL
     * @param list<string>  $deps   依存するハンドル名のリスト
     * @param ?string       $ver    バージョン文字列（null の場合はバージョンなし）
     * @param string        $media  メディアタイプ（例: 'all', 'screen', 'print'）
     */
    public function enqueueStyle(
        string $handle,
        string $src,
        array $deps = [],
        ?string $ver = null,
        string $media = 'all'
    ): void {
        // 同じハンドル名を再登録する場合は上書きする
        $this->styles[$handle] = [
            'src'   => $src,
            'deps'  => $deps,
            'ver'   => $ver,
            'media' => $media,
        ];
    }

    /**
     * スクリプトをキューに追加する。
     *
     * @param string        $handle   スクリプトの識別ハンドル名
     * @param string        $src      スクリプトの URL
     * @param list<string>  $deps     依存するハンドル名のリスト
     * @param ?string       $ver      バージョン文字列（null の場合はバージョンなし）
     * @param bool          $inFooter true の場合はフッターに出力する
     */
    public function enqueueScript(
        string $handle,
        string $src,
        array $deps = [],
        ?string $ver = null,
        bool $inFooter = false
    ): void {
        // 同じハンドル名を再登録する場合は上書きする
        $this->scripts[$handle] = [
            'src'      => $src,
            'deps'     => $deps,
            'ver'      => $ver,
            'inFooter' => $inFooter,
        ];
    }

    /**
     * キューに登録されたスタイルを削除する。
     *
     * @param string $handle 削除対象のハンドル名
     */
    public function dequeueStyle(string $handle): void
    {
        // 指定されたハンドルが存在する場合のみ削除する
        unset($this->styles[$handle]);
    }

    /**
     * キューに登録されたスクリプトを削除する。
     *
     * @param string $handle 削除対象のハンドル名
     */
    public function dequeueScript(string $handle): void
    {
        // 指定されたハンドルが存在する場合のみ削除する
        unset($this->scripts[$handle]);
    }

    /**
     * <link> タグを出力する（inFooter=false のスタイルを対象とする）。
     *
     * バージョンが指定されている場合はクエリ文字列として付加する。
     *
     * @return string 出力する HTML 文字列
     */
    public function printStyles(): string
    {
        $html = '';

        foreach ($this->styles as $handle => $style) {
            // src が空のハンドルはスキップする
            if ($style['src'] === '') {
                continue;
            }

            // バージョンをクエリ文字列として付加する
            $src = $style['src'];
            if ($style['ver'] !== null) {
                $src .= (str_contains($src, '?') ? '&' : '?') . 'ver=' . htmlspecialchars($style['ver'], ENT_QUOTES, 'UTF-8');
            }

            // <link> タグを生成する
            $html .= sprintf(
                '<link rel="stylesheet" id="%s-css" href="%s" media="%s" />' . "\n",
                htmlspecialchars($handle, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($src, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($style['media'], ENT_QUOTES, 'UTF-8')
            );
        }

        return $html;
    }

    /**
     * <script> タグを出力する（$footer 引数で head/footer を切り替える）。
     *
     * @param bool $footer true の場合はフッター用スクリプトを出力する
     * @return string 出力する HTML 文字列
     */
    public function printScripts(bool $footer = false): string
    {
        $html = '';

        foreach ($this->scripts as $handle => $script) {
            // inFooter フラグが指定と一致するもののみ出力する
            if ($script['inFooter'] !== $footer) {
                continue;
            }

            // src が空のハンドルはスキップする
            if ($script['src'] === '') {
                continue;
            }

            // バージョンをクエリ文字列として付加する
            $src = $script['src'];
            if ($script['ver'] !== null) {
                $src .= (str_contains($src, '?') ? '&' : '?') . 'ver=' . htmlspecialchars($script['ver'], ENT_QUOTES, 'UTF-8');
            }

            // <script> タグを生成する
            $html .= sprintf(
                '<script src="%s" id="%s-js"></script>' . "\n",
                htmlspecialchars($src, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($handle, ENT_QUOTES, 'UTF-8')
            );
        }

        return $html;
    }
}
