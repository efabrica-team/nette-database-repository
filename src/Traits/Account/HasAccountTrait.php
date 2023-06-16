<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Account;

trait HasAccountTrait
{
    /** @AccountId */
    private ?int $account_id = null;

    public function getAccountId(): ?int
    {
        return $this->account_id;
    }

    public function setAccountId(?int $accountId): self
    {
        $this->account_id = $accountId;
        return $this;
    }
}
