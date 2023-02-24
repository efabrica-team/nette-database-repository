<?php

namespace Efabrica\NetteDatabaseRepository\Models\Factories;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Nette\Database\Table\Selection;

interface ModelFactoryInterface
{
    public function create(array $data, Selection $table): ActiveRow;
}
