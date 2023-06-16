<?php

namespace Efabrica\NetteDatabaseRepository\Traits\AES;

use Efabrica\NetteDatabaseRepository\Repository\Query;
use Efabrica\NetteDatabaseRepository\Repository\Repository;

/**
 * @mixin Repository
 * @mixin AESRepository
 */
trait AESBehavior
{
    // don't forget to implement AESRepository!
    protected function nonEncryptedQuery(array $where = []): Query
    {
        return $this->query()->withoutEvent(AESEventSubscriber::class);
    }
}
