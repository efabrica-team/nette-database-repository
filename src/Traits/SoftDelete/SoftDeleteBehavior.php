<?php

namespace Efabrica\NetteRepository\Traits\SoftDelete;

use DateTimeImmutable;
use DateTimeInterface;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

/**
 * This behavior will change deletedAt field when entity is deleted and cancel the delete.
 */
class SoftDeleteBehavior extends RepositoryBehavior
{
    private string $column;

    /**
     * @var bool|DateTimeInterface
     */
    private $newValue;

    private bool $filterDeletedRows;

    /** @var array<literal-string, mixed> */
    private array $uniqueColumns;

    /**
     * @param bool|DateTimeInterface $newValue use true if column is bool, null for DateTimeImmutable
     * @param array<literal-string, mixed> $uniqueColumns key is column name, value is new unique value (never usable)
     */
    public function __construct(string $column, $newValue = null, bool $filterDeletedRows = true, array $uniqueColumns = [])
    {
        $this->column = $column;
        $this->newValue = $newValue ?? new DateTimeImmutable();
        $this->filterDeletedRows = $filterDeletedRows;
        $this->uniqueColumns = $uniqueColumns;
    }

    public function shouldFilterDeleted(): bool
    {
        return $this->filterDeletedRows;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @return bool|DateTimeInterface
     */
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * @return array<literal-string, mixed>
     */
    public function getUniqueColumns(): array
    {
        return $this->uniqueColumns;
    }

    public function withoutFilter(): self
    {
        $clone = clone $this;
        $clone->filterDeletedRows = false;
        return $clone;
    }
}
