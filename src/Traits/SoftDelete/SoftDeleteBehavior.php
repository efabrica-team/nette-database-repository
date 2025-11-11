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
    /**
     * @var bool|DateTimeInterface
     */
    private $newValue;

    /**
     * @param bool|DateTimeInterface $newValue use true if column is bool, null for DateTimeImmutable
     */
    public function __construct(private string $column, $newValue = null, private bool $filterDeletedRows = true)
    {
        $this->newValue = $newValue ?? new DateTimeImmutable();
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

    public function withoutFilter(): self
    {
        $clone = clone $this;
        $clone->filterDeletedRows = false;
        return $clone;
    }
}
