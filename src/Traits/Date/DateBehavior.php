<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Date;

use DateTimeImmutable;
use DateTimeInterface;
use Efabrica\NetteDatabaseRepository\Traits\RepositoryBehavior;

/**
 * This behavior will automatically set createdAt and updatedAt fields on insert and update.
 * @see DateEventSubscriber
 */
class DateBehavior extends RepositoryBehavior
{
    private ?string $createdAtField;

    private ?string $updatedAtField;

    /**
     * @param string|null $createdAtField
     * @param string|null $updatedAtField
     */
    public function __construct(?string $createdAtField, ?string $updatedAtField)
    {
        $this->createdAtField = $createdAtField;
        $this->updatedAtField = $updatedAtField;
    }

    public function getCreatedAtField(): ?string
    {
        return $this->createdAtField;
    }

    public function getUpdatedAtField(): ?string
    {
        return $this->updatedAtField;
    }
}
