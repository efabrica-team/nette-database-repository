<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Date;

use DateTimeInterface;

trait CreatedAtEntity
{
    /** @CreatedAt */
    private DateTimeInterface $created_at;

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTimeInterface $createdAt = null): self
    {
        $this->created_at = $createdAt;
        return $this;
    }
}
