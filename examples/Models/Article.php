<?php

namespace Examples\Models;

use Efabrica\NetteDatabaseRepository\Behaviors\SoftDelete\SoftDeleteModelBehavior;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Nette\Utils\DateTime;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $title
 * @property string $body
 * @property ?DateTime $deleted_at
 * @property ?User $user
 */
class Article extends ActiveRow
{
    use SoftDeleteModelBehavior;
}
