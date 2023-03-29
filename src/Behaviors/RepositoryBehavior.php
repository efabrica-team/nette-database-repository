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
}
