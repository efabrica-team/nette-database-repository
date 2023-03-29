<?php

namespace Efabrica\NetteDatabaseRepository\Behavior;

use DateTime;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Repositores\Repository;
use Nette\Database\Table\Selection;

class SoftDeleteBoolBehavior extends SoftDeleteBehavior
{
    public function __construct(Repository $repository, string $deletedAt = 'is_deleted')
    {
        parent::__construct($repository, $deletedAt, true);
    }
}
