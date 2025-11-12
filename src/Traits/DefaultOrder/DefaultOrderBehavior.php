<?php

namespace Efabrica\NetteRepository\Traits\DefaultOrder;

use Efabrica\NetteRepository\Repository\QueryInterface;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class DefaultOrderBehavior extends RepositoryBehavior
{
    private readonly array $params;

    /**
     * @param mixed ...$params
     */
    public function __construct(private readonly string $columns, ...$params)
    {
        $this->params = $params;
    }

    public function apply(QueryInterface $query): void
    {
        if ($query->getOrder() === []) {
            $query->order($this->columns, ...$this->params);
        }
    }
}
