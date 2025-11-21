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

    private ?bool $emptyValue;

    private bool $filterDeletedRows;

    /**
     * @param bool|DateTimeInterface $newValue use true if column is bool, null for DateTimeImmutable
     */
    public function __construct(string $column, $newValue = null, bool $filterDeletedRows = true)
    {
        $this->column = $column;
        $this->newValue = $newValue ?? new DateTimeImmutable();
        $this->emptyValue = $newValue === true ? false : null;
        $this->filterDeletedRows = $filterDeletedRows;
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

    public function getEmptyValue(): ?bool
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
