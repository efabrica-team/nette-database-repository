<?php

namespace Efabrica\NetteRepository\Traits\SoftDelete;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

/**
 * This behavior will change deletedAt field when entity is deleted and cancel the delete.
 */
class SoftDeleteBehavior extends RepositoryBehavior
{
    private string $column;

    private bool|int|DateTimeInterface|Closure $newValue;

    private bool|int|null $emptyValue;

    private bool $filterDeletedRows;

    /**
     * @param bool|int|DateTimeInterface|Closure|null $newValue value to write when deleting:
     *     true for bool columns, int for unix-timestamp columns, DateTimeInterface for datetime columns,
     *     Closure (returning one of the above) to resolve a fresh value per delete,
     *     null for the default new DateTimeImmutable().
     * @param bool|int|null $emptyValue override for "not deleted" value (undefined = use default behavior)
     */
    public function __construct(
        string $column,
        bool|int|DateTimeInterface|Closure|null $newValue = null,
        bool $filterDeletedRows = true,
        bool|int|null $emptyValue = null,
    ) {
        $this->column = $column;
        $this->newValue = $newValue ?? new DateTimeImmutable();
        $this->filterDeletedRows = $filterDeletedRows;

        if (func_num_args() >= 4) {
            $this->emptyValue = $emptyValue;
        } else {
            $this->emptyValue = $newValue === true ? false : null;
        }
    }

    public function shouldFilterDeleted(): bool
    {
        return $this->filterDeletedRows;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getNewValue(): bool|int|DateTimeInterface
    {
        if ($this->newValue instanceof Closure) {
            return ($this->newValue)();
        }
        return $this->newValue;
    }

    public function getEmptyValue(): bool|int|null
    {
        return $this->emptyValue;
    }

    public function withoutFilter(): self
    {
        $clone = clone $this;
        $clone->filterDeletedRows = false;
        return $clone;
    }
}
