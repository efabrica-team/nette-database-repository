<?php

namespace Efabrica\NetteDatabaseRepository\Behavior;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Nette\Database\Table\Selection;

abstract class Behavior
{
    public function beforeInsert(array $data): array
    {
        return $data;
    }

    public function afterInsert(ActiveRow $row, iterable $data): void
    {
    }

    public function beforeUpdate(ActiveRow $row, array $data): array
    {
        return $data;
    }

    public function afterUpdate(ActiveRow $oldRow, ActiveRow $newRow, iterable $data): void
    {
    }

    public function beforeDelete(ActiveRow $row): ?bool
    {
        return null;
    }

    public function afterDelete(ActiveRow $oldRecord): void
    {
    }

    public function beforeSelect(Selection $selection): void
    {
    }

    public function afterSelect(Selection $selection): void
    {
    }
}
