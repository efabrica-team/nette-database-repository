<?php

namespace Examples\Selections;

use Efabrica\NetteDatabaseRepository\Selections\Selection;
use Examples\Models\User;

/**
 * @template-extends Selection<User>
 *
 * @method bool|int|User insert(iterable $data)
 * @method User|null get(mixed $key)
 * @method User|null fetch()
 * @method User[] fetchAll()
 */
class UserSelection extends Selection
{
    public function whereHasGroup(): self
    {
        return $this->where('group_id IS NOT NULL');
    }
}
