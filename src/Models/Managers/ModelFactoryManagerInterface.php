<?php

namespace Efabrica\NetteDatabaseRepository\Models\Managers;

use Efabrica\NetteDatabaseRepository\Models\Factories\ModelFactoryInterface;

interface ModelFactoryManagerInterface
{
    public function createForTable(string $table): ModelFactoryInterface;
}
