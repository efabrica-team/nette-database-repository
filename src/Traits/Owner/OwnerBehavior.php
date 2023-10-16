<?php

namespace Efabrica\NetteRepository\Traits\Owner;

use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class OwnerBehavior extends RepositoryBehavior
{
    private ?string $createdBy;

    private ?string $updatedBy;

    public function __construct(?string $createdBy, ?string $updatedBy)
    {
        $this->createdBy = $createdBy;
        $this->updatedBy = $updatedBy;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }
}
