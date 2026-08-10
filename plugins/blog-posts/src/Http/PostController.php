<?php

namespace TightPress\Plugin\BlogPosts\Http;

use TightPress\Core\Application;
use TightPress\Core\Http\Request;
use TightPress\Core\Http\Response;
use TightPress\Core\Template\TemplateEngine;
use TightPress\Core\Template\ThemeLoader;
use TightPress\Core\Template\TemplateContext;
use TightPress\Core\Template\PageType;
use TightPress\Plugin\BlogPosts\Model\PostRepository;

/**
 * ブログ記事コントローラークラス。
 * 記事一覧（GET /）と記事詳細（GET /posts/{slug}）のリクエストを処理する。
 * テンプレートエンジンを使ってテーマのテンプレートファイルを描画し、
 * HTML レスポンスを返す。
 */
class PostController
{
    /**
     * PostController を生成する。
     *
     * @param PostRepository $repository    記事リポジトリ
     * @param TemplateEngine $templateEngine テンプレートエンジン
     * @param ThemeLoader    $themeLoader   テーマローダー
     * @param Application    $app           DIコンテナ
     */
    public function __construct(
        private readonly PostRepository  $repository,
        private readonly TemplateEngine  $templateEngine,
        private readonly ThemeLoader     $themeLoader,
        private readonly Application     $app,
    ) {}

    /**
     * 記事一覧ページを表示する。
     * 公開済み記事を最大10件取得し、home.php または index.php で描画する。
     *
     * @param  Request $request HTTPリクエスト
     * @param  array   $params  URLパラメータ（このアクションでは使用しない）
     * @return Response         HTML レスポンス
     */
    public function index(Request $request, array $params): Response
    {
        // 公開済み記事を10件取得する
        $posts = $this->repository->findPublished(limit: 10);

        // テンプレートコンテキストに記事一覧とページタイプを設定する
        $context = new TemplateContext();
        $context->posts    = $posts;
        $context->pageType = PageType::Home;

        // テンプレートコンテキストをシングルトンとして登録し、テンプレートタグから参照できるようにする
        $this->app->singleton(TemplateContext::class, fn() => $context);

        // テンプレートファイルを優先順位に従って解決する（home.php → index.php）
        $template = $this->templateEngine->resolve(['home.php', 'index.php']);

        // テンプレートを描画して HTML を取得する
        $html = $this->templateEngine->render($template, $context);

        return Response::html($html);
    }

    /**
     * 記事詳細ページを表示する。
     * スラッグで記事を検索し、公開済みであれば single-post.php 等で描画する。
     * 記事が見つからない、または未公開の場合は 404 レスポンスを返す。
     *
     * @param  Request $request HTTPリクエスト
     * @param  array   $params  URLパラメータ（'slug' キーが必要）
     * @return Response         HTML レスポンスまたは 404 レスポンス
     */
    public function show(Request $request, array $params): Response
    {
        // スラッグで記事を取得する
        $post = $this->repository->findBySlug($params['slug']);

        // 記事が存在しない、または公開済みでない場合は 404 を返す
        if ($post === null || $post->status !== 'publish') {
            return Response::notFound();
        }

        // テンプレートコンテキストに記事情報とページタイプを設定する
        $context = new TemplateContext();
        $context->currentPost = $post;
        $context->posts       = [$post];
        $context->pageType    = PageType::Single;

        // テンプレートコンテキストをシングルトンとして登録し、テンプレートタグから参照できるようにする
        $this->app->singleton(TemplateContext::class, fn() => $context);

        // テンプレートファイルを優先順位に従って解決する
        // single-post.php → single.php → singular.php → index.php の順で試行する
        $template = $this->templateEngine->resolve([
            'single-post.php', 'single.php', 'singular.php', 'index.php',
        ]);

        // テンプレートを描画して HTML を取得する
        $html = $this->templateEngine->render($template, $context);

        return Response::html($html);
    }
}
