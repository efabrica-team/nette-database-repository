<?php

namespace Efabrica\NetteRepository\Traits\DefaultOrder;

use Efabrica\NetteRepository\Repository\QueryInterface;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class DefaultOrderBehavior extends RepositoryBehavior
{
    private const COLUMNS_PATTERN = '/(?<!\.)\b(?!ASC\b)(?!DESC\b)([A-Za-z_][A-Za-z0-9_]*)\b(?!\s*\.)/i';

    private string $columns;

    private array $params;

    /**
     * @param mixed ...$params
     */
    public function __construct(string $columns, ...$params)
    {
        $this->columns = $columns;
        $this->params = $params;
    }

    public function apply(QueryInterface $query): void
    {
        if ($query->getOrder() === []) {
            $query->order(
                preg_replace(self::COLUMNS_PATTERN, $query->getName() . '.$1', $this->columns),
                ...$this->params
            );
        }
    }
}
