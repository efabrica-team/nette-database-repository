<?php

namespace Efabrica\NetteRepository\Repository;

use ArrayAccess;
use Countable;
use Efabrica\NetteRepository\Repository\Scope\Scope;
use Efabrica\NetteRepository\Subscriber\RepositoryEventSubscribers;
use Generator;
use Iterator;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\GroupedSelection;
use ReturnTypeWillChange;

/**
 * @template E of Entity
 */
interface QueryInterface extends Iterator, Countable, ArrayAccess
{
    public function createSelectionInstance(?string $table = null): Query;

    public function createGroupedSelectionInstance(string $table, string $column): GroupedQuery;

    public function __clone();

    /**
     * @param array|E $data Supports multi-insert
     * @return E|int
     */
    public function insert(iterable $data);

    /**
     * @param iterable $data $column => $value
     */
    public function update(iterable $data): int;

    public function delete(?iterable $entities = null): int;

    /**
     * @param array|string|ActiveRow $condition
     * @param mixed                  ...$params
     * @return self&$this
     */
    public function where($condition, ...$params): self;

    /**
     * @param Entity[] $entities
     * @param bool     $original true = do not use unsaved changes
     * @return self&$this
     */
    public function whereEntities(array $entities, bool $original = true): self;

    /**
     * @return self&$this
     */
    public function search(array $columns, string $search): self;

    public function first(): ?Entity;

    public function fetch(): ?Entity;

    /**
     * @return array<E>
     */
    public function fetchAll(): array;

    public function fetchChunked(int $chunkSize = Query::CHUNK_SIZE): Generator;

    public function chunks(int $chunkSize = Query::CHUNK_SIZE): Generator;

    public function count(?string $column = null): int;

    public function getRepository(): Repository;

    public function getEventSubscribers(): RepositoryEventSubscribers;

    public function getBehaviors(): RepositoryBehaviors;

    public function getScope(): Scope;

    /**
     * Returns cloned query with new scope.
     * @param Scope $scope
     * @return self&static
     */
    public function withScope(Scope $scope): self;

    /**
     * @return self&static
     */
    public function scopeRaw(): self;

    /**
     * @return self&static
     */
    public function scopeFull(): self;

    public function __destruct();

    public function getName(): string;

    /**
     * @param bool $throw
     * @return scalar|array|null
     */
    public function getPrimary(bool $throw = true);

    public function getPrimarySequence(): ?string;

    /**
     * @param string $sequence
     * @return self&$this
     */
    public function setPrimarySequence(string $sequence);

    public function getSql(): string;

    /**
     * @param string $key
     */
    public function get($key): ?ActiveRow;

    /**
     * @return mixed
     */
    public function fetchField(?string $column = null);

    /**
     * Fetches all rows as associative array.
     * @param string|int $key column name used for an array key or null for numeric index
     * @param string|int $value column name used for an array value or null for the whole row
     */
    public function fetchPairs($key = null, $value = null): array;

    public function fetchAssoc(string $path): array;

    /**
     * Adds select clause, more calls appends to the end.
     * @param string|string[] $columns for example "column, MD5(column) AS column_md5"
     * @param mixed           ...$params
     * @return self&$this
     */
    public function select($columns, ...$params);

    /**
     * Adds condition for primary key.
     * @param mixed $key
     * @return self&$this
     */
    public function wherePrimary($key);

    /**
     * Adds ON condition when joining specified table, more calls appends with AND.
     * @param string $tableChain table chain or table alias for which you need additional left join condition
     * @param string $condition possibly containing ?
     * @param mixed  ...$params
     * @return self&$this
     */
    public function joinWhere(string $tableChain, string $condition, ...$params);

    /**
     * @param array $parameters
     * @return self&$this
     */
    public function whereOr(array $parameters);

    /**
     * Adds order clause, more calls appends to the end.
     * @param string $columns for example 'column1, column2 DESC'
     * @param mixed  ...$params
     * @return self&$this
     */
    public function order(string $columns, ...$params);

    public function getOrder(): array;

    /**
     * Sets limit clause, more calls rewrite old values.
     * @return self&$this
     */
    public function limit(?int $limit, ?int $offset = null);

    /**
     * Sets offset using page number, more calls rewrite old values.
     * @param int|null $numOfPages number of pages
     * @return self&$this
     */
    public function page(int $page, int $itemsPerPage, &$numOfPages = null);

    /**
     * Sets group clause, more calls rewrite old value.
     * @param string $columns for example "column1, column2"
     * @param mixed  ...$params
     * @return self&$this
     */
    public function group(string $columns, ...$params);

    /**
     * @param string $having
     * @param mixed  ...$params
     * @return self&$this
     */
    public function having(string $having, ...$params);

    /**
     * @return self&$this
     */
    public function alias(string $tableChain, string $alias);

    /**
     * @return mixed
     */
    public function aggregation(string $function, ?string $groupFunction = null);

    /**
     * @return mixed
     */
    public function min(string $column);

    /**
     * @return mixed
     */
    public function max(string $column);

    /**
     * @return mixed
     */
    public function sum(string $column);

    public function accessColumn(?string $key, bool $selectColumn = true): bool;

    public function removeAccessColumn(string $key): void;

    public function getDataRefreshed(): bool;

    /**
     * @return ActiveRow|false|null
     */
    public function getReferencedTable(ActiveRow $row, ?string $table, ?string $column = null);

    /**
     * @param int|string $active
     */
    public function getReferencingTable(string $table, ?string $column = null, $active = null): ?GroupedSelection;

    /**
     * @return E
     */
    #[ReturnTypeWillChange]
    public function current();

    #[ReturnTypeWillChange]
    public function key();

    public function next(): void;

    public function valid(): bool;

    public function offsetSet($key, $value): void;

    public function offsetGet($key): ?ActiveRow;

    public function offsetExists($key): bool;

    public function offsetUnset($key): void;

    /**
     * @return mixed
     */
    public function __call(string $name, array $args);

    /**
     * @return mixed
     */
    public static function __callStatic(string $name, array $args);

    /**
     * @return mixed
     */
    public function __get(string $name);

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set(string $name, $value);

    public function __unset(string $name);

    public function __isset(string $name): bool;
}
