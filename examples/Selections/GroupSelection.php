<?php

namespace Examples\Selections;

use Efabrica\NetteDatabaseRepository\Selections\Selection;
use Examples\Models\Group;
use Iterator;

/**
 * @template-extends Selection<Group>
 * @template-implements Iterator<int, Group>
 *
 * @method bool|int|Group insert(iterable $data)
 * @method Group|null get(mixed $key)
 * @method Group|null fetch()
 * @method Group[] fetchAll()
 */
class GroupSelection extends Selection
{

}
