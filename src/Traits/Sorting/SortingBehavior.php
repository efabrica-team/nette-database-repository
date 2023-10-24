<?php

namespace Efabrica\NetteRepository\Traits\Sorting;

use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class SortingBehavior extends RepositoryBehavior
{
    public const DEFAULT_STEP = 100;

    private string $column;

    private int $step;

    public function __construct(string $column = 'sorting', int $step = self::DEFAULT_STEP)
    {
        $this->column = $column;
        $this->step = $step;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getStep(): int
    {
        return $this->step;
    }
}
