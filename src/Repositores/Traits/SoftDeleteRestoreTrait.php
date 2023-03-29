<?php

namespace Efabrica\NetteDatabaseRepository\Repositores\Traits;

use Efabrica\NetteDatabaseRepository\Behavior\SoftDeleteBehavior;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use LogicException;
use Efabrica\NetteDatabaseRepository\Repositores\Repository;

/**
 * @mixin Repository
 */
trait SoftDeleteRestoreTrait
{
    public function restore(ActiveRow $row): void
    {
        foreach ($this->getBehaviors() as $behavior) {
            if ($behavior instanceof SoftDeleteBehavior) {
                $behavior->restore($row);
                return;
            }
        }
        throw new LogicException('SoftDeleteBehavior is not registered.');
    }
}
