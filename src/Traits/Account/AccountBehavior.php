<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Account;

use Efabrica\NetteDatabaseRepository\Traits\RepositoryBehavior;

class AccountBehavior extends RepositoryBehavior
{
    private string $accountField;

    public function __construct(string $accountField)
    {
        $this->accountField = $accountField;
    }

    public function getAccountField(): string
    {
        return $this->accountField;
    }
}
