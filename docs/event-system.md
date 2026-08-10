# イベントシステム設計

## WordPress フックとの比較

WordPress では `add_action` / `do_action` / `apply_filters` という
グローバル関数ベースのフックシステムを使用している。

TightPress では **PSR-14 Event Dispatcher** に準拠した
型付きイベントクラスで置き換える。

### WordPress フックの問題点

- フック名が文字列のため IDE 補完が効かない
- `apply_filters` の戻り値の型が不明確
- リスナーの登録順（優先度）が暗黙的
- グローバルスコープに状態が蓄積される

---

## TightPress のイベントシステム

### 基本インターフェース（PSR-14 準拠）

```php
namespace TightPress\Core\Event;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private readonly ListenerProviderInterface $listenerProvider
    ) {}

    /**
     * イベントを発行し、全リスナーを呼び出す
     *
     * @template T of object
     * @param  T $event
     * @return T
     */
    public function dispatch(object $event): object;
}
```

### イベントクラスの定義

イベントは値オブジェクトとして定義する。
フィルタブル（戻り値を変更できる）イベントは `StoppableEventInterface` を実装する。

```php
// アクションイベント（副作用のみ、戻り値なし）
namespace TightPress\Plugin\BlogPosts\Events;

final class PostPublished
{
    public function __construct(
        public readonly int    $postId,
        public readonly string $slug,
        public readonly string $title,
    ) {}
}

// フィルタイベント（WordPress の apply_filters 相当）
namespace TightPress\Core\Event;

abstract class FilterEvent implements StoppableEventInterface
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

// フィルタイベントの具体例
namespace TightPress\Plugin\BlogPosts\Events;

use TightPress\Core\Event\FilterEvent;

final class FilterPostContent extends FilterEvent
{
    public function __construct(
        public string $content,   // リスナーが $event->content を書き換える
        public readonly int $postId,
    ) {}
}
```

### リスナーの登録

DI コンテナを通じて登録することで、グローバル状態を排除する。

```php
namespace TightPress\Core\Event;

class ListenerProvider implements \Psr\EventDispatcher\ListenerProviderInterface
{
    /** @var array<class-string, list<callable>> */
    private array $listeners = [];

    /**
     * イベントに対するリスナーを登録する
     *
     * @param class-string $eventClass
     * @param callable     $listener
     */
    public function addListener(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    /**
     * イベントに対応するリスナー一覧を返す
     *
     * @param  object $event
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable;
}
```

### プラグインでの使い方

```php
// BlogPostPlugin::boot() 内での登録例
$listenerProvider->addListener(
    PostPublished::class,
    function (PostPublished $event): void {
        // 記事公開後にサイトマップを更新するなど
    }
);

// フィルタの適用
$listenerProvider->addListener(
    FilterPostContent::class,
    function (FilterPostContent $event): void {
        // コンテンツを加工する（WordPress の the_content フィルタ相当）
        $event->content = wpautop($event->content);
    }
);

// イベント発行
$event = $dispatcher->dispatch(new FilterPostContent($rawContent, $postId));
$filteredContent = $event->content;
```

---

## WordPress フック → TightPress イベント 対応表

| WordPress フック | TightPress イベント | 種別 |
|---|---|---|
| `wp` | `RequestResolved` | Action |
| `template_redirect` | `BeforeTemplateRender` | Action |
| `the_content` | `FilterPostContent` | Filter |
| `the_title` | `FilterPostTitle` | Filter |
| `wp_head` | `RenderHead` | Action |
| `wp_footer` | `RenderFooter` | Action |
| `init` | `ApplicationBooted` | Action |
| `save_post` | `PostSaved` | Action |
| `publish_post` | `PostPublished` | Action |
| `wp_enqueue_scripts` | `EnqueueAssets` | Action |
| `body_class` | `FilterBodyClass` | Filter |
| `pre_get_posts` | `FilterPostQuery` | Filter |

---

## WordPress 互換レイヤー（Phase 1 向け）

既存テーマが `do_action('wp_head')` などを呼び出す場合に対応するため、
WordPress 互換関数をグローバル関数として提供する互換レイヤーを用意する。

```php
// src/Compat/HookCompat.php

/**
 * WordPress 互換: do_action() → EventDispatcher に委譲する
 */
function do_action(string $hookName, mixed ...$args): void
{
    $event = HookBridge::toEvent($hookName, $args);
    if ($event !== null) {
        app(EventDispatcherInterface::class)->dispatch($event);
    }
}

/**
 * WordPress 互換: apply_filters() → FilterEvent に委譲する
 */
function apply_filters(string $hookName, mixed $value, mixed ...$args): mixed
{
    $event = HookBridge::toFilterEvent($hookName, $value, $args);
    if ($event !== null) {
        $dispatched = app(EventDispatcherInterface::class)->dispatch($event);
        return HookBridge::extractValue($dispatched);
    }
    return $value;
}
```

この互換レイヤーにより、twentytwentythree テーマの `<?php do_action('wp_head'); ?>` が
そのまま動作する。Phase が進むにつれて互換レイヤーへの依存を減らしていく。
