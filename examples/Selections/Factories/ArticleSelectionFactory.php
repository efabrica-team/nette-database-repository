<?php

namespace Examples\Selections\Factories;

use Efabrica\NetteDatabaseRepository\Selections\Factories\SelectionFactoryInterface;
use Examples\Selections\ArticleSelection;

interface ArticleSelectionFactory extends SelectionFactoryInterface
{
    public function create(string $tableName): ArticleSelection;
}
