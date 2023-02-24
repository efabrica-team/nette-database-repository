<?php

namespace Efabrica\NetteDatabaseRepository\Repositores\Managers;

use Efabrica\NetteDatabaseRepository\Repositores\Repository;

interface RepositoryManagerInterface
{
    public function createForTable(string $table): ?Repository;
}
