<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Date;

use DateTimeInterface;

trait UpdatedAtEntity
{
    /** @UpdatedAt */
    private DateTimeInterface $updated_at;

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt = null): self
    {
        $this->updated_at = $updatedAt;
        return $this;
    }
}
