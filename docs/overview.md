# TightPress 概要

## プロジェクト概要

TightPress は WordPress と互換性を持つことを目指した PHP 製 CMS です。
WordPress の良い点を継承しつつ、設計上の課題を改善することを目指します。

## 基本方針

- **言語:** PHP 8.1 以上
- **名前空間:** `TightPress\` を起点とした PSR-4 名前空間
- **DB対応:** SQLite / MySQL / PostgreSQL の3種に対応
- **依存管理:** Composer

## WordPress から改善する点

| 課題 | 改善方針 |
|---|---|
| グローバル変数・グローバル関数が多い | DI コンテナ + 名前空間 + 型付きクラスで管理 |
| `wp_posts` が汎用テーブルとして流用されている | 用途別にテーブルを分離する |
| フック (`add_action` / `apply_filters`) が暗黙的 | PSR-14 Event Dispatcher + 型付きイベントクラスで置き換え |
| 自動ロードが整備されていない | Composer PSR-4 オートロードに統一 |
| テスト困難な設計 | DI・インターフェース中心でテスタブルに |

## フェーズ一覧

| フェーズ | 目標 |
|---|---|
| Phase 1 | テーマ描画 + 記事・固定ページの表示 |
| Phase 2 | ユーザー認証 |
| Phase 3 | 管理画面ログイン |
| Phase 4 | 管理画面から記事・固定ページの投稿・編集 |
| Phase 5 | プラグインのインストール・アンインストール |

## 関連ドキュメント

- [ディレクトリ構成](./directory-structure.md)
- [データベース設計](./database-schema.md)
- [アーキテクチャ設計](./architecture.md)
- [イベントシステム設計](./event-system.md)
- [プラグインシステム設計](./plugin-system.md)
- [フェーズ別実装計画](./phases.md)
