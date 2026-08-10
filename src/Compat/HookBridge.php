<?php

declare(strict_types=1);

namespace TightPress\Compat;

use TightPress\Compat\Events\GenericActionEvent;
use TightPress\Compat\Events\GenericFilterEvent;
use TightPress\Compat\Events\WpFooterEvent;
use TightPress\Compat\Events\WpHeadEvent;

/**
 * WordPress のフック名と TightPress イベントクラスを相互変換するブリッジ
 *
 * do_action / apply_filters から呼ばれ、フック名に対応するイベントクラスの
 * インスタンスを生成する。専用クラスのマッピングがない場合は汎用イベントを使用する。
 * プラグインは register() で独自イベントクラスをマッピングに追加できる。
 */
class HookBridge
{
    /**
     * フック名 → イベントクラスのマッピング
     *
     * キーが WordPress フック名、値がそれに対応する TightPress イベントクラス名。
     *
     * @var array<string, class-string>
     */
    private static array $map = [
        'wp_head'   => WpHeadEvent::class,
        'wp_footer' => WpFooterEvent::class,
    ];

    /**
     * フック名をイベントクラス名に変換する
     *
     * マッピングに登録されていないフック名は GenericActionEvent を返す。
     *
     * @param  string      $hook WordPress フック名
     * @return class-string      対応するイベントクラス名
     */
    public static function toEventClass(string $hook): string
    {
        return self::$map[$hook] ?? GenericActionEvent::class;
    }

    /**
     * do_action 用のイベントインスタンスを生成する
     *
     * マッピングに専用クラスが登録されている場合はそのクラスをインスタンス化する。
     * 登録されていない場合は GenericActionEvent にフック名と引数を格納して返す。
     *
     * @param  string $hook WordPress フック名
     * @param  array  $args do_action に渡された追加引数
     * @return object       生成したイベントオブジェクト
     */
    public static function toEvent(string $hook, array $args): object
    {
        if (isset(self::$map[$hook])) {
            // マッピングがある場合は専用イベントクラスをインスタンス化
            $class = self::$map[$hook];
            return new $class();
        }

        // マッピングがない場合は汎用イベントにフック名と引数を詰めて返す
        return new GenericActionEvent($hook, $args);
    }

    /**
     * apply_filters 用のフィルターイベントインスタンスを生成する
     *
     * フィルター対象の値とフック名、追加引数を GenericFilterEvent に格納する。
     * リスナーは $event->value を書き換えることでフィルターを適用する。
     *
     * @param  string              $hook  WordPress フック名
     * @param  mixed               $value フィルター対象の初期値
     * @param  array               $args  apply_filters に渡された追加引数
     * @return GenericFilterEvent         生成したフィルターイベント
     */
    public static function toFilterEvent(string $hook, mixed $value, array $args): GenericFilterEvent
    {
        return new GenericFilterEvent($hook, $value, $args);
    }

    /**
     * フィルターイベントから値を取り出す
     *
     * リスナーによって書き換えられた $event->value を返す。
     * GenericFilterEvent 以外のイベントが渡された場合は null を返す。
     *
     * @param  object     $event フィルターイベントオブジェクト
     * @return mixed             フィルター適用後の値、または null
     */
    public static function extractValue(object $event): mixed
    {
        if ($event instanceof GenericFilterEvent) {
            // リスナーが書き換えた value を返す
            return $event->value;
        }

        return null;
    }

    /**
     * フック名のマッピングを追加する
     *
     * プラグインが独自イベントクラスを登録する際に使用する。
     * 既存のキーを上書きすることで専用クラスへの差し替えも可能。
     *
     * @param  string      $hook       WordPress フック名
     * @param  class-string $eventClass 対応させるイベントクラス名
     * @return void
     */
    public static function register(string $hook, string $eventClass): void
    {
        // 指定されたフック名に対してイベントクラスをマッピングに登録する
        self::$map[$hook] = $eventClass;
    }
}
