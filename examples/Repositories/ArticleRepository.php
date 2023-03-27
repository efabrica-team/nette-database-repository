<?php

namespace Examples\Repositories;

use Efabrica\NetteDatabaseRepository\Behaviors\SoftDelete\SoftDeleteBehavior;
use Efabrica\NetteDatabaseRepository\Repositores\Repository;
use Examples\Models\Article;
use Examples\Selections\ArticleSelection;

/**
 * @template-extends Repository<ArticleSelection, Article>
 */
class ArticleRepository extends Repository
{
    use SoftDeleteBehavior;

    public function getTableName(): string
    {
        return 'articles';
    }
}
