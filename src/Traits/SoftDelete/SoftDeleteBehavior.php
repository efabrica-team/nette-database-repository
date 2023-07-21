<?php

namespace Efabrica\NetteDatabaseRepository\Traits\SoftDelete;

use DateTimeImmutable;
use DateTimeInterface;
use Efabrica\NetteDatabaseRepository\Traits\RepositoryBehavior;

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

    /**
     * @param bool|DateTimeInterface $newValue use true if column is bool, null for DateTimeImmutable
     */
    public function __construct(string $column, $newValue = null)
    {
        $this->column = $column;
        $this->newValue = $newValue ?? new DateTimeImmutable();
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getNewValue()
    {
        return $this->newValue;
    }
}
