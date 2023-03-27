<?php

namespace Examples\Selections;

use Efabrica\NetteDatabaseRepository\Behaviors\SoftDelete\SoftDeleteSelectionBehavior;
use Efabrica\NetteDatabaseRepository\Selections\Selection;
use Examples\Models\Article;
use Iterator;

/**
 * @template-extends Selection<Article>
 * @template-implements Iterator<int, Article>
 *
 * @method bool|int|Article insert(iterable $data)
 * @method Article|null get(mixed $key)
 * @method Article|null fetch()
 * @method Article[] fetchAll()
 */
class ArticleSelection extends Selection
{
    use SoftDeleteSelectionBehavior;
}
