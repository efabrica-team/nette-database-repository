<?php

namespace Efabrica\NetteRepository\Traits\Sorting;

use Efabrica\NetteRepository\Traits\DefaultOrder\DefaultOrderBehavior;

class SortingBehavior extends DefaultOrderBehavior
{
    public const DEFAULT_STEP = 100;
    public const COLUMN = 'sorting';

    public function __construct(private readonly string $column = self::COLUMN, private readonly int $step = self::DEFAULT_STEP, private readonly bool $ascending = true)
    {
        parent::__construct($this->column . ' ' . $this->getDirection());
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getStep(): int
    {
        return $this->step;
    }

    public function isAscending(): bool
    {
        return $this->ascending;
    }

    public function getDirection(): string
    {
        return $this->isAscending() ? 'ASC' : 'DESC';
    }
}
