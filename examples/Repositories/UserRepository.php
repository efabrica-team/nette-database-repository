<?php

namespace Examples\Repositories;

use Efabrica\NetteDatabaseRepository\Repositores\Repository;
use Examples\Models\User;
use Examples\Selections\UserSelection;

/**
 * @template-extends Repository<UserSelection, User>
 */
class UserRepository extends Repository
{
    public function getTableName(): string
    {
        return 'users';
    }
}
