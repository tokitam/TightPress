<?php

namespace TightPress\Plugin\Pages\Http;

use TightPress\Core\Application;
use TightPress\Core\Http\Request;
use TightPress\Core\Http\Response;
use TightPress\Core\Template\TemplateEngine;
use TightPress\Core\Template\TemplateContext;
use TightPress\Core\Template\PageType;
use TightPress\Plugin\Pages\Model\PageRepository;

/**
 * 固定ページコントローラークラス。
 * 固定ページ詳細（GET /{slug}）のリクエストを処理する。
 * テンプレートエンジンを使ってテーマのテンプレートファイルを描画し、
 * HTML レスポンスを返す。
 */
class PageController
{
    /**
     * PageController を生成する。
     *
     * @param PageRepository $repository    固定ページリポジトリ
     * @param TemplateEngine $templateEngine テンプレートエンジン
     * @param Application    $app           DIコンテナ
     */
    public function __construct(
        private readonly PageRepository  $repository,
        private readonly TemplateEngine  $templateEngine,
        private readonly Application     $app,
    ) {}

    /**
     * 固定ページ詳細を表示する。
     * スラッグでページを検索し、公開済みであれば page-{slug}.php 等で描画する。
     * ページが見つからない、または未公開の場合は 404 レスポンスを返す。
     *
     * テンプレートの解決順序:
     * 1. page-{slug}.php（スラッグ固有テンプレート）
     * 2. ページに設定された個別テンプレート（$page->template）
     * 3. page.php（固定ページ共通テンプレート）
     * 4. singular.php（個別コンテンツ共通テンプレート）
     * 5. index.php（フォールバック）
     *
     * @param  Request $request HTTPリクエスト
     * @param  array   $params  URLパラメータ（'slug' キーが必要）
     * @return Response         HTML レスポンスまたは 404 レスポンス
     */
    public function show(Request $request, array $params): Response
    {
        // スラッグで固定ページを取得する
        $page = $this->repository->findBySlug($params['slug']);

        // ページが存在しない、または公開済みでない場合は 404 を返す
        if ($page === null || $page->status !== 'publish') {
            return Response::notFound();
        }

        // テンプレートコンテキストにページ情報とページタイプを設定する
        // Page も currentPost に格納することで、WordPress 互換関数が参照できるようにする
        $context = new TemplateContext();
        $context->currentPost = $page;
        $context->posts       = [$page];
        $context->pageType    = PageType::Page;

        // テンプレートコンテキストをシングルトンとして登録し、テンプレートタグから参照できるようにする
        $this->app->singleton(TemplateContext::class, fn() => $context);

        // テンプレートファイルを優先順位に従って解決する
        // page-{slug}.php → 個別テンプレート → page.php → singular.php → index.php の順で試行する
        // 空文字列のテンプレート名は locate_template でスキップされる
        $template = $this->templateEngine->resolve([
            "page-{$page->slug}.php",
            $page->template ?? '',
            'page.php',
            'singular.php',
            'index.php',
        ]);

        // テンプレートを描画して HTML を取得する
        $html = $this->templateEngine->render($template, $context);

        return Response::html($html);
    }
}
