<?php

namespace Efabrica\NetteRepository\Traits\Filter;

use Efabrica\NetteRepository\Repository\QueryInterface;

interface FilterBehaviorInterface
{
    public function applyFilter(QueryInterface $query): void;
}
