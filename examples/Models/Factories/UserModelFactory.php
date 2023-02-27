<?php

namespace Examples\Models\Factories;

use Efabrica\NetteDatabaseRepository\Models\Factories\ModelFactoryInterface;
use Examples\Models\User;
use Nette\Database\Table\Selection;

interface UserModelFactory extends ModelFactoryInterface
{
    public function create(array $data, Selection $table): User;
}
