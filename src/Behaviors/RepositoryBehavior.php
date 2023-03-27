<?php

namespace Efabrica\NetteDatabaseRepository\Behaviors;

use Efabrica\NetteDatabaseRepository\Exceptions\RepositoryException;
use Efabrica\NetteDatabaseRepository\Helpers\HookIgnore;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Selections\Selection;
use Throwable;

trait RepositoryBehavior
{
    abstract public function getTableName(): string;

    abstract public function query(): Selection;

    /**
     * @param iterable $data
     *
     * @return bool|int|ActiveRow
     * @throws RepositoryException
     * @throws Throwable
     */
    abstract public function insert(iterable $data);

    abstract public function insertMany(array $items): int;

    /**
     * @param ActiveRow|int|string $record
     * @param iterable $data
     *
     * @return ActiveRow|null
     * @throws RepositoryException
     * @throws Throwable
     */
    abstract public function update($record, iterable $data): ?ActiveRow;

    /**
     * @param ActiveRow|int|string $record
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
}
