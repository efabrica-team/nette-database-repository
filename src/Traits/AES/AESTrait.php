<?php

namespace Efabrica\NetteDatabaseRepository\Traits\AES;

use Efabrica\NetteDatabaseRepository\Repository\Query;
use Efabrica\NetteDatabaseRepository\Repository\Repository;

/**
 * @mixin Repository
 */
trait AESTrait
{
    protected function nonEncryptedQuery(array $where = []): Query
    {
        return $this->query()->withoutEvent(AESEventSubscriber::class);
    }
}
