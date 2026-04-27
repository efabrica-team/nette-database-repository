<?php

namespace Efabrica\NetteRepository\Traits\Filter;

use Efabrica\NetteRepository\Repository\QueryInterface;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

/**
 * This behavior adds default where condition to every query.
 */
class FilterBehavior extends RepositoryBehavior implements FilterBehaviorInterface
{
    private array $where;

    public function __construct(array $where = [])
    {
        $this->where = $where;
    }

    public function applyFilter(QueryInterface $query): void
    {
        $tablePrefixedWhere = [];
        foreach ($this->where as $key => $value) {
            if (!str_contains($key, '.')) {
                $key = $query->getRepository()->getTableName() . '.' . $key;
            }
            $tablePrefixedWhere[$key] = $value;
        }

        $query->where($tablePrefixedWhere);
    }
}
