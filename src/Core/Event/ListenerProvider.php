<?php

declare(strict_types=1);

namespace TightPress\Core\Event;

use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * PSR-14 準拠のリスナープロバイダー
 *
 * イベントクラスとリスナー（コールバック）のマッピングを管理する。
 * EventDispatcher はこのクラスからリスナー一覧を取得してイベントを配布する。
 */
class ListenerProvider implements ListenerProviderInterface
{
    /**
     * イベントクラス → リスナーリストのマッピング
     *
     * @var array<class-string, list<callable>>
     */
    private array $listeners = [];

    /**
     * イベントクラスにリスナーを登録する
     *
     * @param class-string $eventClass 対象のイベントクラス名
     * @param callable     $listener   イベントを受け取るコールバック
     * @return void
     */
    public function addListener(string $eventClass, callable $listener): void
    {
        // 同一イベントクラスに複数のリスナーを追加可能
        $this->listeners[$eventClass][] = $listener;
    }

    /**
     * イベントに対するリスナー一覧を返す（PSR-14 必須実装）
     *
     * イベントオブジェクトのクラス名をキーにリスナーリストを検索する。
     * 登録がない場合は空配列を返す。
     *
     * @param object $event 配布されるイベントオブジェクト
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        // イベントクラス名に対応するリスナーリストを返す。なければ空配列
        return $this->listeners[$event::class] ?? [];
    }
}
