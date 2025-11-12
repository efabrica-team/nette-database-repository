<?php

namespace Efabrica\NetteRepository\Traits\Filter;

use Efabrica\NetteRepository\Repository\QueryInterface;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

/**
 * This behavior adds default where condition to every query.
 */
class FilterBehavior extends RepositoryBehavior implements FilterBehaviorInterface
{
    public function __construct(private readonly array $where = [])
    {
    }

    public function applyFilter(QueryInterface $query): void
    {
        $query->where($this->where);
    }
}
