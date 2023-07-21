<?php

namespace Efabrica\NetteDatabaseRepository\Traits\TreeTraverse;

use Efabrica\NetteDatabaseRepository\Traits\RepositoryBehavior;

class TreeTraverseBehavior extends RepositoryBehavior
{
    private string $leftColumn;

    private string $rightColumn;

    private string $depthColumn;

    private string $parentColumn;

    private string $sortingColumn;

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
