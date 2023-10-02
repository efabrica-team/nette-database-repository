<?php

namespace Efabrica\NetteRepository\Traits\Account;

use Efabrica\NetteRepository\Traits\RepositoryBehavior;

final class AccountBehavior extends RepositoryBehavior
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
