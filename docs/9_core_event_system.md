# コアイベントシステム

## 概要

TightPress のコアイベントシステムは PSR-14（Event Dispatcher）に準拠した実装です。
WordPress のフック機構（`add_action` / `do_action` / `add_filter` / `apply_filters`）を
PSR-14 イベントとして扱えるようにブリッジ層を提供します。

## クラス構成

```
src/Core/Event/
├── ListenerProvider.php   # PSR-14 ListenerProviderInterface 実装
└── EventDispatcher.php    # PSR-14 EventDispatcherInterface 実装

src/Compat/
├── HookBridge.php         # フック名 ↔ イベントクラスの変換
└── Events/
    ├── WpHeadEvent.php       # wp_head フック対応イベント
    ├── WpFooterEvent.php     # wp_footer フック対応イベント
    ├── GenericActionEvent.php # 汎用アクションイベント
    └── GenericFilterEvent.php # 汎用フィルターイベント
```

## 処理フロー

### アクションフック（do_action）

```
do_action('wp_head')
  → HookBridge::toEvent('wp_head', [])  → WpHeadEvent のインスタンス
  → EventDispatcher::dispatch($event)
  → ListenerProvider::getListenersForEvent($event) でリスナー取得
  → 各リスナーを順に呼び出す
```

マッピングされていないフック名の場合:

```
do_action('init')
  → HookBridge::toEvent('init', [])  → GenericActionEvent('init', []) のインスタンス
  → EventDispatcher::dispatch($event)
  → リスナーが $event->hook でフック名を判別して処理
```

### フィルターフック（apply_filters）

```
apply_filters('the_title', $title)
  → HookBridge::toFilterEvent('the_title', $title, []) → GenericFilterEvent のインスタンス
  → EventDispatcher::dispatch($event)
  → リスナーが $event->value を書き換える
  → HookBridge::extractValue($event) で変更後の値を取り出す
```

## 使い方

### リスナーの登録

```php
use TightPress\Core\Event\ListenerProvider;
use TightPress\Compat\Events\WpHeadEvent;

$provider = new ListenerProvider();

// WpHeadEvent に対してリスナーを登録する
$provider->addListener(WpHeadEvent::class, function (WpHeadEvent $event): void {
    echo '<meta name="generator" content="TightPress">';
});
```

### イベントの dispatch

```php
use TightPress\Core\Event\EventDispatcher;

$dispatcher = new EventDispatcher($provider);
$dispatcher->dispatch(new WpHeadEvent());
```

### 独自イベントクラスのマッピング登録

```php
use TightPress\Compat\HookBridge;
use MyPlugin\Events\MyCustomEvent;

// 'my_custom_hook' を MyCustomEvent にマッピングする
HookBridge::register('my_custom_hook', MyCustomEvent::class);
```

## 伝播の停止

`Psr\EventDispatcher\StoppableEventInterface` を実装したイベントは
`isPropagationStopped()` が `true` を返した時点でリスナーへの配布を中止できます。

```php
use Psr\EventDispatcher\StoppableEventInterface;

class MyStoppableEvent implements StoppableEventInterface
{
    private bool $stopped = false;

    public function stopPropagation(): void
    {
        $this->stopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }
}
```

## 設計方針

- **PSR-14 準拠**: インターフェースへの依存を維持し、将来的に別実装への差し替えを可能にする
- **WordPress 互換**: `HookBridge` により既存の WordPress フック API との橋渡しを担う
- **拡張性**: `HookBridge::register()` でプラグインが独自イベントクラスを追加できる
- **フィルター値の変更**: `GenericFilterEvent::$value` を `readonly` にせず、リスナーが直接書き換える設計
