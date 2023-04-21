<?php

namespace Examples\Selections;

use Efabrica\NetteDatabaseRepository\Selections\Selection;
use Examples\Models\Article;

/**
 * @template-extends Selection<Article>
 *
 * @method bool|int|Article insert(iterable $data)
 * @method Article|null get(mixed $key)
 * @method Article|null fetch()
 * @method Article[] fetchAll()
 */
class ArticleSelection extends Selection
{
    //
}
