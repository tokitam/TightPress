<?php

declare(strict_types=1);

namespace TightPress\Core\Template;

/**
 * テーマディレクトリのパス・URL を管理するクラス
 *
 * 有効なテーマのディレクトリパスやファイルパス・URLの生成を担当する。
 * テンプレートエンジンがテーマファイルを参照する際の起点となる。
 */
class ThemeLoader
{
    /**
     * テーマディレクトリの親ディレクトリの絶対パス（末尾スラッシュなし）
     */
    private string $themesDir;

    /**
     * 有効なテーマ名（ディレクトリ名）
     */
    private string $activeTheme;

    /**
     * @param string $themesDir   themes/ ディレクトリの絶対パス
     * @param string $activeTheme 有効なテーマ名（例: 'twentytwentythree'）
     */
    public function __construct(string $themesDir, string $activeTheme)
    {
        // 末尾スラッシュを除去して内部で統一した形式で保持する
        $this->themesDir   = rtrim($themesDir, '/');
        $this->activeTheme = $activeTheme;
    }

    /**
     * テーマルートの絶対パスを返す
     *
     * 例: /path/to/tightpress/themes/twentytwentythree
     *
     * @return string テーマルートの絶対パス
     */
    public function getThemeDir(): string
    {
        return "{$this->themesDir}/{$this->activeTheme}";
    }

    /**
     * テーマ内のファイルパスを返す（存在チェックなし）
     *
     * @param string $relativePath ファイル名または相対パス（例: 'header.php'）
     * @return string テーマ内ファイルの絶対パス
     */
    public function path(string $relativePath): string
    {
        // 引数の先頭スラッシュを除去してから結合する
        return $this->getThemeDir() . '/' . ltrim($relativePath, '/');
    }

    /**
     * テーマ内のファイル URL を返す
     *
     * siteUrl が空文字の場合はパスのみを返す。
     *
     * @param string $relativePath ファイル名または相対パス
     * @param string $siteUrl      サイトの基底 URL（例: 'https://example.com'）
     * @return string テーマ内ファイルの URL または相対パス
     */
    public function url(string $relativePath, string $siteUrl = ''): string
    {
        // siteUrl が空の場合はパスのみ返す
        $base = $siteUrl !== '' ? rtrim($siteUrl, '/') : '';
        return $base . '/themes/' . $this->activeTheme . '/' . ltrim($relativePath, '/');
    }

    /**
     * 有効なテーマ名を返す
     *
     * @return string テーマ名（ディレクトリ名）
     */
    public function getActiveTheme(): string
    {
        return $this->activeTheme;
    }
}
