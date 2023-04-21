<?php

namespace Efabrica\NetteDatabaseRepository\Selections\Managers;

use Efabrica\NetteDatabaseRepository\Selections\Factories\SelectionFactoryInterface;

interface SelectionFactoryManagerInterface
{
    public function createForRepository(string $repository): SelectionFactoryInterface;
}
