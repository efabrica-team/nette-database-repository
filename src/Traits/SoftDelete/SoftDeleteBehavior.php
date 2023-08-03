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

    private bool $filterDeleted;

    /**
     * @param bool|DateTimeInterface $newValue use true if column is bool, null for DateTimeImmutable
     */
    public function __construct(string $column, $newValue = null, bool $filterDeleted = true)
    {
        $this->column = $column;
        $this->newValue = $newValue ?? new DateTimeImmutable();
        $this->filterDeleted = $filterDeleted;
    }

    public function shouldFilterDeleted(): bool
    {
        return $this->filterDeleted;
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
}
