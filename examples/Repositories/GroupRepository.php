<?php

namespace Examples\Repositories;

use Efabrica\NetteDatabaseRepository\Repositores\Repository;
use Examples\Models\Group;
use Examples\Selections\GroupSelection;

/**
 * @template-extends Repository<GroupSelection, Group>
 */
class GroupRepository extends Repository
{
    public function getTableName(): string
    {
        return 'groups';
    }
}
