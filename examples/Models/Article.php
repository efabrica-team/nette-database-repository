<?php

namespace Examples\Models;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $title
 * @property string $body
 * @property ?User $user
 */
class Article extends ActiveRow
{
    //
}
