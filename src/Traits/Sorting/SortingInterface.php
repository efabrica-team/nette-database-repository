<?php

namespace Efabrica\NetteRepository\Traits\Sorting;

interface SortingInterface
{
    public function getSorting(): SortingBehavior;

    public function moveUp($record, array $where = [], bool $up = true): bool;

    public function moveDown($record, array $where = []): bool;

    public function insertBefore(int $sorting, array $where): void;

    public function insertAfter(int $sorting, array $where): void;
}
