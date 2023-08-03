<?php

namespace Efabrica\NetteRepository\Traits\DefaultWhere;

use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class DefaultWhereBehavior extends RepositoryBehavior
{
    private array $where;

    public function __construct(array $where)
    {
        $this->where = $where;
    }

    public function apply(Query $query): void
    {
        $query->where($this->where);
    }
}
