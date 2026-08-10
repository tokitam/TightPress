<?php

declare(strict_types=1);

namespace TightPress\Core\Template;

/**
 * テンプレートに渡すコンテキスト情報を保持するクラス
 *
 * WordPress の have_posts() / the_post() に相当するループ制御と、
 * 現在のページ種別・投稿データをまとめて管理する。
 */
class TemplateContext
{
    /**
     * 現在の投稿オブジェクト（single / page 時に設定）
     *
     * Post または Page エンティティが入る（共通インターフェースは将来定義）。
     */
    public ?object $currentPost = null;

    /**
     * クエリ結果の投稿一覧（一覧ページ時に設定）
     *
     * @var list<object>
     */
    public array $posts = [];

    /**
     * 現在のページ種別
     */
    public PageType $pageType = PageType::Home;

    /**
     * ループカーソル（-1 = ループ開始前）
     *
     * advanceCursor() を呼ぶたびにインクリメントされ、対応する posts のインデックスを示す。
     */
    private int $cursor = -1;

    /**
     * the_post() 相当：ループカーソルを1つ進めて currentPost を更新する
     *
     * @return bool 次の投稿があった場合 true、投稿がなくなった場合 false
     */
    public function advanceCursor(): bool
    {
        // カーソルを1つ進める
        $this->cursor++;

        // 対応するインデックスに投稿が存在するか確認する
        if (isset($this->posts[$this->cursor])) {
            $this->currentPost = $this->posts[$this->cursor];
            return true;
        }

        // 投稿がなくなった場合は false を返す
        return false;
    }

    /**
     * have_posts() 相当：次の投稿があるかどうかを確認する
     *
     * advanceCursor() を呼ぶ前に使用し、ループを継続するかどうかを判定する。
     *
     * @return bool 次の投稿が存在する場合 true
     */
    public function hasPosts(): bool
    {
        // 現在のカーソルの次のインデックスに投稿が存在するか確認する
        return isset($this->posts[$this->cursor + 1]);
    }

    /**
     * ループカーソルをリセットする（再度ループする場合に使用）
     *
     * 同一コンテキストで複数回ループしたい場合に呼び出す。
     *
     * @return void
     */
    public function rewindPosts(): void
    {
        // カーソルを初期状態（ループ開始前）に戻す
        $this->cursor      = -1;
        $this->currentPost = null;
    }

    /**
     * テンプレートに extract() で展開する変数の連想配列を返す
     *
     * テンプレートファイル内から $tp_context や $posts で参照できるようにする。
     *
     * @return array<string, mixed> テンプレートに展開する変数の連想配列
     */
    public function toArray(): array
    {
        return [
            // コンテキスト本体をテンプレート内から参照できるようにする
            'tp_context' => $this,
            // WordPress テーマが参照する可能性のある変数もセットしておく
            'posts'      => $this->posts,
        ];
    }
}
