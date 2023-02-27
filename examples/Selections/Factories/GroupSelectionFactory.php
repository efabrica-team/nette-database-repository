<?php

namespace Examples\Selections\Factories;

use Efabrica\NetteDatabaseRepository\Selections\Factories\SelectionFactoryInterface;
use Examples\Selections\GroupSelection;

interface GroupSelectionFactory extends SelectionFactoryInterface
{
    public function create(string $tableName): GroupSelection;
}
