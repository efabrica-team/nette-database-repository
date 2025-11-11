<?php

namespace Efabrica\NetteRepository\Traits\Date;

use Efabrica\NetteRepository\Traits\RepositoryBehavior;

/**
 * This behavior will automatically set createdAt and updatedAt fields on insert and update.
 * @see DateEventSubscriber
 */
class DateBehavior extends RepositoryBehavior
{
    public function __construct(private readonly ?string $createdAtField, private readonly ?string $updatedAtField)
    {
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
