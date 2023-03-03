<?php

namespace Examples\Repositories;

use Efabrica\NetteDatabaseRepository\Repositores\Repository;
use Examples\Models\Article;
use Examples\Selections\ArticleSelection;

/**
 * @template-extends Repository<ArticleSelection, Article>
 */
class ArticleRepository extends Repository
{
    public function getTableName(): string
    {
        return 'articles';
    }
}
