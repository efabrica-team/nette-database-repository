<?php

namespace Efabrica\NetteDatabaseRepository\Behaviors;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Nette\Database\Table\GroupedSelection;

/**
 * @template M of ActiveRow
 */
trait SelectionBehavior
{
    /**
     * @return M|null
     */
    abstract public function get($key): ?ActiveRow;

    /**
     * @return M|null
     */
	abstract public function fetch(): ?ActiveRow;

	abstract public function fetchField(?string $column = null);

	abstract public function fetchPairs($key = null, $value = null): array;

    /**
     * @return M[]
     */
	abstract public function fetchAll(): array;

	abstract public function fetchAssoc(string $path): array;

	abstract public function select($columns, ...$params);

	abstract public function wherePrimary($key);

	abstract public function where($condition, ...$params);

	abstract public function whereOr(array $parameters);

	abstract public function joinWhere(string $tableChain, string $condition, ...$params);

	abstract public function order(string $columns, ...$params);

	abstract public function limit(?int $limit, ?int $offset = null);

	abstract public function page(int $page, int $itemsPerPage, &$numOfPages = null);

	abstract public function group(string $columns, ...$params);

	abstract public function having(string $having, ...$params);

	abstract public function alias(string $tableChain, string $alias);

	abstract public function aggregation(string $function, ?string $groupFunction = null);

	abstract public function count(?string $column = null): int;

	abstract public function min(string $column);

	abstract public function max(string $column);

	abstract public function sum(string $column);

    /**
     * @param iterable $data
     *
     * @return M|int|bool
     */
	abstract public function insert(iterable $data);

	abstract public function update(iterable $data): int;

	abstract public function delete(): int;

	abstract public function getReferencedTable(ActiveRow $row, ?string $table, ?string $column = null);

	abstract public function getReferencingTable(string $table, ?string $column = null, $active = null): ?GroupedSelection;

    abstract public function getHookIgnores(): array;

    /**
     * @return static
     */
    abstract public function importHookIgnores(array $hookIgnores): self;

    /**
     * @return static
     */
    abstract public function resetHookIgnores(): self;

    /**
     * @return static
     */
    abstract public function ignoreHook(string $hookName): self;

    /**
     * @return static
     */
    abstract public function ignoreHookType(string $hookType, string $hookName = null): self;

    /**
     * @return static
     */
    abstract public function ignoreBehavior(?string $traitName, string $hookType = null, string $hookName = null): self;

    /**
     * @return static
     */
    abstract public function ignoreHooks(): self;
}
