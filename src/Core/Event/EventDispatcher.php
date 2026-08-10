<?php

declare(strict_types=1);

namespace TightPress\Core\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * PSR-14 準拠のイベントディスパッチャー
 *
 * ListenerProvider からリスナーを取得し、順番に呼び出す。
 * StoppableEventInterface を実装したイベントは途中で伝播を停止できる。
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * コンストラクタ
     *
     * @param ListenerProviderInterface $listenerProvider リスナーの取得元
     */
    public function __construct(
        private readonly ListenerProviderInterface $listenerProvider
    ) {}

    /**
     * イベントを dispatch してリスナーを順に呼び出す
     *
     * StoppableEventInterface を実装したイベントは isPropagationStopped() が
     * true を返した時点で残りのリスナーへの配布を中止する。
     *
     * @template T of object
     * @param  T $event 配布するイベントオブジェクト
     * @return T        配布後のイベントオブジェクト（同じインスタンス）
     */
    public function dispatch(object $event): object
    {
        // リスナープロバイダーからイベントに対応するリスナーを取得して順に呼び出す
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            // 伝播停止チェック：StoppableEventInterface を実装していてかつ停止フラグが立っていれば中断
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
            // リスナーにイベントを渡して実行する
            $listener($event);
        }

        return $event;
    }
}
