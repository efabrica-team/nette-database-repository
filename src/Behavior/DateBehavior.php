<?php

namespace Efabrica\NetteDatabaseRepository\Behavior;

use DateTime;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;

class DateBehavior extends Behavior
{
    private ?string $createdAtField;

    private ?string $updatedAtField;

    public function __construct(?string $createdAtField = 'created_at', ?string $updatedAtField = 'updated_at')
    {
        $this->createdAtField = $createdAtField;
        $this->updatedAtField = $updatedAtField;
    }

    public function beforeInsert(array $data): array
    {
        if ($this->createdAtField !== null) {
            $data[$this->createdAtField] ??= new DateTime();
        }
        if ($this->updatedAtField !== null) {
            $data[$this->updatedAtField] ??= new DateTime();
        }
        return $data;
    }

    public function beforeUpdate(ActiveRow $row, array $data): array
    {
        if ($this->updatedAtField !== null) {
            $data[$this->updatedAtField] ??= new DateTime();
        }
        return $data;
    }
}
