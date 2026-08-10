# データベース設計

## 設計方針

WordPress の `wp_posts` は記事・固定ページ・メディア・メニュー・リビジョンなど
あらゆるものを1テーブルに詰め込む設計になっている。
TightPress ではエンティティの種類に応じてテーブルを分離し、
スキーマを明確にする。

## テーブル一覧

### コアが管理するテーブル

| テーブル名 | 用途 |
|---|---|
| `options` | サイト設定（`get_option` 相当） |
| `users` | ユーザーアカウント |
| `user_meta` | ユーザー追加情報 |
| `sessions` | ログインセッション |
| `media` | メディアファイル情報 |
| `taxonomies` | タクソノミー定義（category / tag など） |
| `terms` | タクソノミーの各ターム |

### blog-posts プラグインが管理するテーブル

| テーブル名 | 用途 |
|---|---|
| `posts` | ブログ記事本体 |
| `post_meta` | 記事追加情報 |
| `post_term_relationships` | 記事とタームの紐付け |
| `comments` | コメント |

### pages プラグインが管理するテーブル

| テーブル名 | 用途 |
|---|---|
| `pages` | 固定ページ本体 |
| `page_meta` | 固定ページ追加情報 |

---

## テーブル定義

### `options`

```sql
CREATE TABLE options (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       VARCHAR(191) NOT NULL UNIQUE,
    value      TEXT,
    autoload   BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
```

### `users`

```sql
CREATE TABLE users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    login         VARCHAR(60) NOT NULL UNIQUE,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name  VARCHAR(250),
    role          VARCHAR(20) NOT NULL DEFAULT 'subscriber',
    status        VARCHAR(20) NOT NULL DEFAULT 'active',
    registered_at DATETIME NOT NULL,
    created_at    DATETIME NOT NULL,
    updated_at    DATETIME NOT NULL
);
```

### `user_meta`

```sql
CREATE TABLE user_meta (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    meta_key   VARCHAR(191) NOT NULL,
    meta_value TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_user_meta_user_id (user_id),
    INDEX idx_user_meta_key (meta_key)
);
```

### `sessions`

```sql
CREATE TABLE sessions (
    id         VARCHAR(128) PRIMARY KEY,
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    payload    TEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL
);
```

### `media`

```sql
CREATE TABLE media (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER REFERENCES users(id) ON DELETE SET NULL,
    filename    VARCHAR(255) NOT NULL,
    path        VARCHAR(1000) NOT NULL,
    mime_type   VARCHAR(100) NOT NULL,
    file_size   INTEGER NOT NULL DEFAULT 0,
    alt_text    VARCHAR(500),
    caption     TEXT,
    description TEXT,
    meta        JSON,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL
);
```

### `taxonomies`

```sql
CREATE TABLE taxonomies (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    slug        VARCHAR(191) NOT NULL UNIQUE,
    label       VARCHAR(255) NOT NULL,
    description TEXT,
    hierarchical BOOLEAN NOT NULL DEFAULT FALSE,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL
);
```

### `terms`

```sql
CREATE TABLE terms (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    taxonomy_id INTEGER NOT NULL REFERENCES taxonomies(id) ON DELETE CASCADE,
    parent_id   INTEGER REFERENCES terms(id) ON DELETE SET NULL,
    slug        VARCHAR(191) NOT NULL,
    name        VARCHAR(255) NOT NULL,
    description TEXT,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL,
    UNIQUE (taxonomy_id, slug)
);
```

### `posts`（blog-posts プラグイン）

```sql
CREATE TABLE posts (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    author_id    INTEGER REFERENCES users(id) ON DELETE SET NULL,
    slug         VARCHAR(191) NOT NULL UNIQUE,
    title        VARCHAR(500) NOT NULL,
    content      LONGTEXT,
    excerpt      TEXT,
    status       VARCHAR(20) NOT NULL DEFAULT 'draft',
    comment_status VARCHAR(20) NOT NULL DEFAULT 'open',
    thumbnail_id INTEGER REFERENCES media(id) ON DELETE SET NULL,
    published_at DATETIME,
    created_at   DATETIME NOT NULL,
    updated_at   DATETIME NOT NULL,
    INDEX idx_posts_status (status),
    INDEX idx_posts_published_at (published_at)
);
-- status: 'draft' | 'publish' | 'private' | 'trash'
```

### `post_meta`（blog-posts プラグイン）

```sql
CREATE TABLE post_meta (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id    INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    meta_key   VARCHAR(191) NOT NULL,
    meta_value TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_post_meta_post_id (post_id),
    INDEX idx_post_meta_key (meta_key)
);
```

### `post_term_relationships`（blog-posts プラグイン）

```sql
CREATE TABLE post_term_relationships (
    post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    term_id INTEGER NOT NULL REFERENCES terms(id) ON DELETE CASCADE,
    PRIMARY KEY (post_id, term_id)
);
```

### `comments`（blog-posts プラグイン）

```sql
CREATE TABLE comments (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id     INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    parent_id   INTEGER REFERENCES comments(id) ON DELETE CASCADE,
    author_id   INTEGER REFERENCES users(id) ON DELETE SET NULL,
    author_name VARCHAR(255),
    author_email VARCHAR(100),
    content     TEXT NOT NULL,
    status      VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME NOT NULL
);
-- status: 'pending' | 'approved' | 'spam' | 'trash'
```

### `pages`（pages プラグイン）

```sql
CREATE TABLE pages (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    parent_id    INTEGER REFERENCES pages(id) ON DELETE SET NULL,
    author_id    INTEGER REFERENCES users(id) ON DELETE SET NULL,
    slug         VARCHAR(191) NOT NULL UNIQUE,
    title        VARCHAR(500) NOT NULL,
    content      LONGTEXT,
    status       VARCHAR(20) NOT NULL DEFAULT 'draft',
    thumbnail_id INTEGER REFERENCES media(id) ON DELETE SET NULL,
    sort_order   INTEGER NOT NULL DEFAULT 0,
    template     VARCHAR(255),
    published_at DATETIME,
    created_at   DATETIME NOT NULL,
    updated_at   DATETIME NOT NULL
);
-- status: 'draft' | 'publish' | 'private' | 'trash'
```

### `page_meta`（pages プラグイン）

```sql
CREATE TABLE page_meta (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id    INTEGER NOT NULL REFERENCES pages(id) ON DELETE CASCADE,
    meta_key   VARCHAR(191) NOT NULL,
    meta_value TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
```

---

## WordPress との対応表

| WordPress テーブル | TightPress での扱い |
|---|---|
| `wp_posts` (post_type=post) | `posts` テーブル（blog-posts プラグイン） |
| `wp_posts` (post_type=page) | `pages` テーブル（pages プラグイン） |
| `wp_posts` (post_type=attachment) | `media` テーブル（コア） |
| `wp_postmeta` | `post_meta` / `page_meta` に分離 |
| `wp_users` | `users` テーブル |
| `wp_usermeta` | `user_meta` テーブル |
| `wp_options` | `options` テーブル |
| `wp_terms` | `terms` テーブル |
| `wp_term_taxonomy` | `taxonomies` + `terms` に統合 |
| `wp_term_relationships` | `post_term_relationships` テーブル |
| `wp_comments` | `comments` テーブル（blog-posts プラグイン） |
| `wp_commentmeta` | `comments.meta` JSON カラムに統合 |
