<?php

namespace Efabrica\NetteDatabaseRepository\Behaviors;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Selections\Selection;

trait KeepDefaultBehavior
{
    use RepositoryBehavior;

    protected function isDefaultField(): string
    {
        return 'is_default';
    }

    protected function keepDefaultWhere(Selection $selection): Selection
    {
        return $selection;
    }

    final public function beforeInsertSetDefault(array $data): array
    {
        $data[$this->isDefaultField()] = $this->keepDefaultWhere($this->query())->count('*') === 0;
        return $data;
    }

    final public function afterDeleteAssignNewDefault(ActiveRow $record): void
    {
        if (!$record->{$this->isDefaultField()}) {
            return;
        }

        $newDefaultRecord = $this->keepDefaultWhere($this->query())->fetch();
        if ($newDefaultRecord === null) {
            return;
        }

        $this->update($newDefaultRecord, [
            $this->isDefaultField() => true,
        ]);
    }

    final public function afterSoftDeleteAssignNewDefault(ActiveRow $record): void
    {
        $this->afterDeleteAssignNewDefault($record);
    }
}
