<?php

namespace Efabrica\NetteDatabaseRepository\Behaviors;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Nette\Utils\DateTime;

trait TimestampsBehavior
{
    use RepositoryBehavior;

    protected function createdAtField(): string
    {
        return 'created_at';
    }

    protected function updatedAtField(): string
    {
        return 'updated_at';
    }

    final public function beforeInsertActionName(array $data): array
    {
        $date = new DateTime();
        if (!isset($data[$this->createdAtField()])) {
            $data[$this->createdAtField()] = $date;
        }
        if (!isset($data[$this->updatedAtField()])) {
            $data[$this->updatedAtField()] = $date;
        }
        return $data;
    }

    final public function beforeUpdateActionName(ActiveRow $record, array $data): array
    {
        if (!isset($data[$this->updatedAtField()])) {
            $data[$this->updatedAtField()] = new DateTime();
        }
        return $data;
    }
}
