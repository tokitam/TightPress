<?php

namespace TightPress\Core\Config;

/**
 * 設定管理クラス。
 *
 * ネストした連想配列をドット記法で参照できるラッパー。
 * 例: $config->get('database.sqlite.path') で
 *      $data['database']['sqlite']['path'] を取得できる。
 */
class Config
{
    /**
     * コンストラクタ。
     *
     * @param array<string, mixed> $data 設定データ（ネストした連想配列）
     */
    public function __construct(private readonly array $data) {}

    /**
     * ドット記法で設定値を取得する。
     *
     * @param  string $key     ドット区切りのキー（例: 'database.driver'）
     * @param  mixed  $default キーが存在しない場合のデフォルト値
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // ドットでキーを分解してセグメントの配列を作る
        $segments = explode('.', $key);

        // 先頭から順にネストを掘り下げていく
        $current = $this->data;

        foreach ($segments as $segment) {
            // 現在位置が配列でない、またはセグメントが存在しない場合はデフォルト値を返す
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }

            // 次の階層に進む
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * 設定値が存在するか確認する。
     *
     * @param  string $key ドット区切りのキー
     * @return bool
     */
    public function has(string $key): bool
    {
        // get() でデフォルト値のセンチネル（ユニークオブジェクト）と比較して存在チェックする
        $sentinel = new \stdClass();

        return $this->get($key, $sentinel) !== $sentinel;
    }
}
