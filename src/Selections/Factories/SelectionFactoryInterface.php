<?php

namespace Efabrica\NetteDatabaseRepository\Selections\Factories;

use Efabrica\NetteDatabaseRepository\Selections\Selection;

interface SelectionFactoryInterface
{
    public function create(string $tableName): Selection;
}
