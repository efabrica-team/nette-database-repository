<?php

namespace Efabrica\NetteRepository\Traits\DefaultOrder;

use Efabrica\NetteRepository\Repository\QueryInterface;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class DefaultOrderBehavior extends RepositoryBehavior
{
    private string $columns;

    private array $params;

    public function __construct(string $columns, ...$params)
    {
        $this->columns = $columns;
        $this->params = $params;
    }

    public function apply(QueryInterface $query): void
    {
        if ($query->getOrder() === []) {
            $query->order($this->columns, ...$this->params);
        }
    }
}
