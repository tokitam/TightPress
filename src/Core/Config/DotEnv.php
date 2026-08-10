<?php

declare(strict_types=1);

namespace TightPress\Core\Config;

/**
 * .env ファイルを読み込んで $_ENV・putenv に展開するパーサー。
 *
 * 外部ライブラリへの依存なしに実装する。
 * 既存の環境変数（Docker / CI で注入された値）は上書きしない。
 */
class DotEnv
{
    /**
     * .env ファイルを読み込んで環境変数に展開する。
     *
     * @param string $path .env ファイルの絶対パス
     */
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            // コメント行はスキップする
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // = を含まない行はスキップする
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // クォート（"value" または 'value'）を除去する
            if (preg_match('/^(["\'])(.*)\1$/', $value, $m)) {
                $value = $m[2];
            }

            // 既存の環境変数は上書きしない
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}
