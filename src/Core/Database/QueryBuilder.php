<?php

namespace TightPress\Core\Database;

/**
 * クエリビルダー。
 *
 * メソッドチェーンで SQL クエリを組み立て、ConnectionInterface 経由で実行する。
 * Phase 1 では WHERE は AND 結合のみサポート。OR や LIKE は将来の課題とする。
 */
class QueryBuilder
{
    /**
     * 操作対象のテーブル名。
     *
     * @var string
     */
    private string $table = '';

    /**
     * SELECT するカラムのリスト。
     *
     * @var array<int, string>
     */
    private array $selects = ['*'];

    /**
     * WHERE 条件のリスト（AND で結合される）。
     *
     * 各要素は ['column' => string, 'value' => mixed] の形式。
     *
     * @var array<int, array{column: string, value: mixed}>
     */
    private array $wheres = [];

    /**
     * ORDER BY のカラム名（null は ORDER BY なし）。
     *
     * @var string|null
     */
    private ?string $orderBy = null;

    /**
     * ORDER BY の方向（'ASC' または 'DESC'）。
     *
     * @var string
     */
    private string $orderDir = 'ASC';

    /**
     * LIMIT 値（null は LIMIT なし）。
     *
     * @var int|null
     */
    private ?int $limit = null;

    /**
     * OFFSET 値（null は OFFSET なし）。
     *
     * @var int|null
     */
    private ?int $offset = null;

    /**
     * コンストラクタ。
     *
     * @param ConnectionInterface $connection 使用するデータベース接続
     */
    public function __construct(private readonly ConnectionInterface $connection) {}

    /**
     * 操作対象のテーブルを指定する。
     *
     * @param  string $table テーブル名
     * @return static
     */
    public function table(string $table): static
    {
        // イミュータブルな操作のため clone して返す
        $clone        = clone $this;
        $clone->table = $table;

        return $clone;
    }

    /**
     * SELECT するカラムを指定する。
     *
     * @param  string ...$columns カラム名（可変長引数）
     * @return static
     */
    public function select(string ...$columns): static
    {
        $clone          = clone $this;
        $clone->selects = $columns;

        return $clone;
    }

    /**
     * WHERE 条件を追加する（複数呼び出しは AND で結合される）。
     *
     * @param  string $column カラム名
     * @param  mixed  $value  比較する値
     * @return static
     */
    public function where(string $column, mixed $value): static
    {
        $clone           = clone $this;
        $clone->wheres[] = ['column' => $column, 'value' => $value];

        return $clone;
    }

    /**
     * ORDER BY を指定する。
     *
     * @param  string $column    並び順のカラム名
     * @param  string $direction 'ASC' または 'DESC'
     * @return static
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $clone           = clone $this;
        $clone->orderBy  = $column;
        $clone->orderDir = strtoupper($direction);

        return $clone;
    }

    /**
     * LIMIT を指定する。
     *
     * @param  int $limit 取得する最大行数
     * @return static
     */
    public function limit(int $limit): static
    {
        $clone        = clone $this;
        $clone->limit = $limit;

        return $clone;
    }

    /**
     * OFFSET を指定する。
     *
     * @param  int $offset スキップする行数
     * @return static
     */
    public function offset(int $offset): static
    {
        $clone         = clone $this;
        $clone->offset = $offset;

        return $clone;
    }

    /**
     * クエリを実行して全行を返す。
     *
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        // SELECT 文を組み立てて実行する
        $sql      = $this->buildSelect();
        $bindings = $this->buildBindings();

        return $this->connection->select($sql, $bindings);
    }

    /**
     * クエリを実行して最初の1行を返す。結果がない場合は null を返す。
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        // LIMIT 1 を追加して実行する
        $rows = $this->limit(1)->get();

        return $rows[0] ?? null;
    }

    /**
     * INSERT を実行して挿入した行の ID を返す。
     *
     * @param  array<string, mixed> $data 挿入するカラム名 => 値のマップ
     * @return int 挿入した行の ID
     */
    public function insert(array $data): int
    {
        // カラム名と値プレースホルダーを組み立てる
        $columns      = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        return $this->connection->insert($sql, array_values($data));
    }

    /**
     * UPDATE を実行して影響した行数を返す。
     *
     * @param  array<string, mixed> $data 更新するカラム名 => 値のマップ
     * @return int 影響行数
     */
    public function update(array $data): int
    {
        // SET 句を組み立てる（各カラムを "column = ?" 形式にする）
        $setClauses = array_map(
            fn(string $col) => "{$col} = ?",
            array_keys($data)
        );
        $setClause = implode(', ', $setClauses);

        $sql = "UPDATE {$this->table} SET {$setClause}";

        // WHERE 句を追加する
        $whereBindings = [];
        if (!empty($this->wheres)) {
            [$whereSql, $whereBindings] = $this->buildWhere();
            $sql .= " WHERE {$whereSql}";
        }

        // バインディングは SET の値 + WHERE の値の順
        $bindings = array_merge(array_values($data), $whereBindings);

        return $this->connection->update($sql, $bindings);
    }

    /**
     * DELETE を実行して影響した行数を返す。
     *
     * @return int 影響行数
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        // WHERE 句を追加する
        $bindings = [];
        if (!empty($this->wheres)) {
            [$whereSql, $bindings] = $this->buildWhere();
            $sql .= " WHERE {$whereSql}";
        }

        return $this->connection->delete($sql, $bindings);
    }

    /**
     * SELECT 文全体を組み立てる。
     *
     * @return string 組み立てた SELECT SQL
     */
    private function buildSelect(): string
    {
        // SELECT 句を組み立てる
        $columns = implode(', ', $this->selects);
        $sql     = "SELECT {$columns} FROM {$this->table}";

        // WHERE 句を追加する（条件が存在する場合のみ）
        if (!empty($this->wheres)) {
            [$whereSql] = $this->buildWhere();
            $sql .= " WHERE {$whereSql}";
        }

        // ORDER BY 句を追加する
        if ($this->orderBy !== null) {
            $sql .= " ORDER BY {$this->orderBy} {$this->orderDir}";
        }

        // LIMIT 句を追加する
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        // OFFSET 句を追加する
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * WHERE 句の SQL 文字列とバインディング値の配列を返す。
     *
     * @return array{0: string, 1: array<int, mixed>} [WHERE句文字列, バインディング値配列]
     */
    private function buildWhere(): array
    {
        // 各 WHERE 条件を "column = ?" 形式に変換して AND で結合する
        $clauses  = array_map(
            fn(array $w) => "{$w['column']} = ?",
            $this->wheres
        );
        $whereSql = implode(' AND ', $clauses);

        // バインディング値を順番に取り出す
        $bindings = array_map(fn(array $w) => $w['value'], $this->wheres);

        return [$whereSql, $bindings];
    }

    /**
     * SELECT 実行用のバインディングパラメータを組み立てる。
     *
     * @return array<int, mixed>
     */
    private function buildBindings(): array
    {
        if (empty($this->wheres)) {
            return [];
        }

        // WHERE 条件の値だけをバインディングとして返す
        [, $bindings] = $this->buildWhere();

        return $bindings;
    }
}
