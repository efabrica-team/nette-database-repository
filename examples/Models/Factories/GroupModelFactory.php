<?php

namespace Examples\Models\Factories;

use Efabrica\NetteDatabaseRepository\Models\Factories\ModelFactoryInterface;
use Examples\Models\Group;
use Nette\Database\Table\Selection;

interface GroupModelFactory extends ModelFactoryInterface
{
    public function create(array $data, Selection $table): Group;
}
