<?php

namespace Examples\Models;

use Efabrica\NetteDatabaseRepository\Casts\JsonArrayCast;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;

/**
 * @property int $id
 * @property string $name
 * @property array $permissions
 */
class Group extends ActiveRow
{
    public function getCasts(): array
    {
        return [
            'permissions' => JsonArrayCast::class,
        ];
    }
}
