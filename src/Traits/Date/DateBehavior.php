<?php

namespace Efabrica\NetteRepository\Traits\Date;

use DateTimeImmutable;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

/**
 * This behavior will automatically set createdAt and updatedAt fields on insert and update.
 * @see DateEventSubscriber
 */
class DateBehavior extends RepositoryBehavior
{
    private ?string $createdAtField;

    private ?string $updatedAtField;
    private bool $date;

    public function __construct(?string $createdAtField, ?string $updatedAtField, bool $date = true)
    {
        $this->createdAtField = $createdAtField;
        $this->updatedAtField = $updatedAtField;
        $this->date = $date;
    }

    public function getCreatedAtField(): ?string
    {
        return $this->createdAtField;
    }

    public function getUpdatedAtField(): ?string
    {
        return $this->updatedAtField;
    }

    /**
     * @return DateTimeImmutable|true
     */
    public function getNewValue()
    {
        return $this->date ? new \DateTimeImmutable() : true;
    }
}
