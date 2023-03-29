<?php

namespace Efabrica\NetteDatabaseRepository\Behaviors;

use Efabrica\NetteDatabaseRepository\Exceptions\LastItemException;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Selections\Selection;

trait KeepLastBehavior
{
    use RepositoryBehavior;

    protected function keepLastWhere(Selection $selection): Selection
    {
        return $selection;
    }

    final public function beforeDeleteCheckIfNotLast(ActiveRow $record): void
    {
        if ($this->keepLastWhere($this->query())->count('*') === 1) {
            throw new LastItemException('Record (' . $record->getPrimary() . ') can\'t be deleted because it is last item.');
        }
    }

    final public function beforeSoftDeleteCheckIfNotLast(ActiveRow $record): void
    {
        $this->beforeDeleteCheckIfNotLast($record);
    }
}
