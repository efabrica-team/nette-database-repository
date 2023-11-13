<?php

namespace Efabrica\NetteRepository\Traits\Date;

use Efabrica\NetteRepository\Traits\RepositoryBehavior;

/**
 * This behavior will automatically set createdAt and updatedAt fields on insert and update.
 * @see DateEventSubscriber
 */
class DateBehavior extends RepositoryBehavior
{
    private ?string $createdAtField;

    private ?string $updatedAtField;

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
