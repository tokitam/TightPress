# ディレクトリ構成

## プロジェクト全体

```
tightpress/
├── composer.json
├── composer.lock
├── index.php                  # エントリポイント
├── bootstrap.php              # アプリケーション初期化
├── config/
│   ├── app.php                # アプリケーション設定
│   └── database.php           # DB接続設定
├── src/
│   └── (コアライブラリ → 後述)
├── plugins/                   # 組み込みプラグイン + サードパーティ
│   ├── blog-posts/            # 記事管理プラグイン（Phase 1）
│   └── pages/                 # 固定ページプラグイン（Phase 1）
├── themes/
│   └── twentytwentythree/     # 初期対応テーマ
├── uploads/                   # アップロードファイル
├── docs/                      # プロジェクトドキュメント
└── tests/                     # PHPUnit テスト
    ├── Unit/
    └── Integration/
```

## src/ コア構成

```
src/
├── Core/
│   ├── Application.php            # DIコンテナ・アプリケーション本体
│   ├── Config/
│   │   └── Config.php             # 設定値の読み込み・管理
│   ├── Database/
│   │   ├── ConnectionInterface.php
│   │   ├── Connection.php         # ファクトリ（ドライバを選択して返す）
│   │   ├── QueryBuilder.php       # SQLクエリビルダー
│   │   └── Drivers/
│   │       ├── DriverInterface.php
│   │       ├── MySQLDriver.php
│   │       ├── SQLiteDriver.php
│   │       └── PostgreSQLDriver.php
│   ├── Event/
│   │   ├── EventDispatcher.php    # PSR-14準拠イベントディスパッチャー
│   │   ├── ListenerProvider.php
│   │   └── Events/                # コアが発行するイベントクラス群
│   ├── Http/
│   │   ├── Request.php            # HTTPリクエスト
│   │   ├── Response.php           # HTTPレスポンス
│   │   ├── Router.php             # URLルーティング
│   │   └── Middleware/
│   │       ├── MiddlewareInterface.php
│   │       └── Pipeline.php       # ミドルウェアチェーン
│   ├── Plugin/
│   │   ├── PluginInterface.php    # プラグインが実装すべきインターフェース
│   │   ├── PluginManager.php      # プラグイン登録・起動・停止
│   │   └── PluginMeta.php         # プラグインのメタ情報
│   ├── Template/
│   │   ├── TemplateEngine.php     # テーマテンプレートの読み込み・描画
│   │   ├── ThemeLoader.php        # テーマ検出・有効化
│   │   └── TemplateContext.php    # テンプレートに渡すコンテキスト
│   └── Option/
│       └── OptionRepository.php   # サイト設定の読み書き（WordPressのget_option相当）
├── Auth/                          # Phase 2以降
│   ├── User.php
│   ├── AuthManager.php
│   ├── SessionDriver.php
│   └── PasswordHasher.php
└── Admin/                         # Phase 3以降
    ├── AdminRouter.php
    └── Controllers/
```

## プラグイン構成（blog-posts を例に）

```
plugins/blog-posts/
├── plugin.php                 # プラグインエントリポイント（メタ情報 + PluginInterface実装）
├── composer.json              # プラグイン独自の依存（任意）
└── src/
    ├── BlogPostPlugin.php     # PluginInterface を implements
    ├── Model/
    │   ├── Post.php           # エンティティ
    │   └── PostRepository.php # DB操作
    ├── Http/
    │   └── PostController.php # ルートハンドラ
    └── Events/
        ├── PostCreated.php
        └── PostUpdated.php
```
