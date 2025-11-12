<?php

namespace Efabrica\NetteRepository\Traits\TreeTraverse;

use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class TreeTraverseBehavior extends RepositoryBehavior
{
    private readonly string $leftColumn;

    private readonly string $rightColumn;

    private readonly string $depthColumn;

    private readonly string $parentColumn;

    private readonly string $sortingColumn;

    public function __construct(?string $leftColumn, ?string $rightColumn, ?string $depthColumn, ?string $parentColumn, ?string $sortingColumn)
    {
        $this->leftColumn = $leftColumn ?? 'lft';
        $this->rightColumn = $rightColumn ?? 'rgt';
        $this->depthColumn = $depthColumn ?? 'depth';
        $this->parentColumn = $parentColumn ?? 'parent_id';
        $this->sortingColumn = $sortingColumn ?? 'sorting';
    }

    public function getLeftColumn(): string
    {
        return $this->leftColumn;
    }

    public function getRightColumn(): string
    {
        return $this->rightColumn;
    }

    public function getDepthColumn(): string
    {
        return $this->depthColumn;
    }

    public function getParentColumn(): string
    {
        return $this->parentColumn;
    }

    public function getSortingColumn(): string
    {
        return $this->sortingColumn;
    }
}
