<?php

namespace Efabrica\NetteDatabaseRepository\Behaviors;

use Efabrica\NetteDatabaseRepository\Exceptions\RepositoryException;
use Efabrica\NetteDatabaseRepository\Helpers\HookIgnore;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Selections\Selection;
use Throwable;

/**
 * @template M of ActiveRow
 */
trait RepositoryBehavior
{
    abstract public function getTableName(): string;

    abstract public function query(): Selection;

    /**
     * @param iterable $data
     *
     * @return bool|int|M
     * @throws RepositoryException
     * @throws Throwable
     */
    abstract public function insert(iterable $data);

    abstract public function insertMany(array $items): int;

    /**
     * @param M|int|string $record
     * @param iterable $data
     *
     * @return M|null
     * @throws RepositoryException
     * @throws Throwable
     */
    abstract public function update($record, iterable $data): ?ActiveRow;

    /**
     * @param M|int|string $record
     *
     * @return bool
     * @throws Throwable
     */
    abstract public function delete($record): bool;

    /**
     * @param HookIgnore[] $hookIgnores
     */
    abstract protected function findMethods(string $methodPrefix, array $hookIgnores = []): array;

    /**
     * @param HookIgnore[] $hookIgnores
     */
    abstract public function callMethods(string $methodPrefix, array $args, array $hookIgnores = []): bool;

    abstract public function getHookIgnores(): array;

    /**
     * @return static
     */
    abstract public function importHookIgnores(array $hookIgnores);

    /**
     * @return static
     */
    abstract public function resetHookIgnores();

    /**
     * @return static
     */
    abstract public function ignoreHook(string $hookName);

    /**
     * @return static
     */
    abstract public function ignoreHookType(string $hookType, string $hookName = null);

    /**
     * @return static
     */
    abstract public function ignoreBehavior(?string $traitName, string $hookType = null, string $hookName = null);

    /**
     * @return static
     */
    abstract public function ignoreHooks();
}
