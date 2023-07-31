<?php

namespace Efabrica\NetteRepository\Traits\AES;

use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Repository\Repository;

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
