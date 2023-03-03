<?php

namespace Examples\Models\Factories;

use Efabrica\NetteDatabaseRepository\Models\Factories\ModelFactoryInterface;
use Examples\Models\Article;
use Nette\Database\Table\Selection;

interface ArticleModelFactory extends ModelFactoryInterface
{
    public function create(array $data, Selection $table): Article;
}
