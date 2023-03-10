<?php

namespace Efabrica\NetteDatabaseRepository\Behavior;

use DateTime;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;

class DateBehavior extends Behavior
{
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(?string $createdAt = 'created_at', ?string $updatedAt = 'updated_at')
    {
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function beforeInsert(array $data): array
    {
        if ($this->createdAt !== null) {
            $data[$this->createdAt] ??= new DateTime();
        }
        if ($this->updatedAt !== null) {
            $data[$this->updatedAt] ??= new DateTime();
        }
        return $data;
    }

    public function beforeUpdate(ActiveRow $row, array $data): array
    {
        if ($this->updatedAt !== null && !isset($data[$this->updatedAt])) {
            $data[$this->updatedAt] ??= new DateTime();
        }
        return $data;
    }
}