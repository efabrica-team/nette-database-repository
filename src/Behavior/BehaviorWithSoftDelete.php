<?php

namespace Efabrica\NetteDatabaseRepository\Behavior;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;

interface BehaviorWithSoftDelete
{
    public function beforeSoftDelete(ActiveRow $row): void;

    public function afterSoftDelete(ActiveRow $row): void;
}
