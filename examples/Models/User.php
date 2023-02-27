<?php

namespace Examples\Models;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;

/**
 * @property int $id
 * @property int|null $group_id
 * @property string $name
 * @property string $email
 * @property ?Group $group
 */
class User extends ActiveRow
{
    public function hasGroup(): bool
    {
        return $this->group_id !== null;
    }
}
