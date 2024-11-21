<?php

namespace Efabrica\NetteRepository\Traits\Sorting;

use Efabrica\NetteRepository\Repository\Entity;

interface SortingInterface
{
    public function getSorting(): SortingBehavior;

    /**
     * @param Entity|scalar $record
     */
    public function moveUp($record, array $where = [], bool $up = true): bool;

    /**
     * @param Entity|scalar $record
     */
    public function moveDown($record, array $where = []): bool;

    /**
     * @param Entity|scalar $record
     */
    public function moveTop($record, array $where = [], bool $up = true): bool;

    /**
     * @param Entity|scalar $record
     */
    public function moveBottom($record, array $where = []): bool;

    public function insertBefore(int $sorting, array $where): void;

    public function insertAfter(int $sorting, array $where): void;
}
