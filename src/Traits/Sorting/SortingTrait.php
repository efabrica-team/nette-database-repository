<?php

namespace Efabrica\NetteRepository\Traits\Sorting;

use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Repository;

/**
 * @mixin Repository
 */
trait SortingTrait
{
    public function sortingField(): string
    {
        return 'sorting';
    }

    public function sortingStep(): int
    {
        return 100;
    }

    public function moveUp($record, array $where = [], bool $up = true): bool
    {
        $record = $this->getRecordForShifting($record, $where);
        if ($record === null) {
            return false;
        }

        $sortField = $this->getTableName() . '.' . $this->sortingField();
        $where[$sortField . ($up ? ' < ?' : ' > ?')] = $record[$this->sortingField()];

        $upperRecord = $this->findBy($where)->order($sortField . ($up ? ' DESC' : ' ASC'))->limit(1)->fetch();
        if (!$upperRecord) {
            return false;
        }

        $upperSorting = $upperRecord[$this->sortingField()];
        $upperRecord[$this->sortingField()] = $record[$this->sortingField()];
        $record[$this->sortingField()] = $upperSorting;
        $this->updateEntities($upperRecord, $record);
        return true;
    }

    public function moveDown($record, array $where = []): bool
    {
        return $this->moveUp($record, $where, false);
    }

    public function insertAfter(int $sorting, array $where): void
    {
        $sortField = $this->getTableName() . '.' . $this->sortingField();
        $where[$sortField . ' <= ?'] = $sorting;
        $this->findBy($where)->update([$this->sortingField() . '-=' => $this->sortingStep()]);
    }

    public function insertBefore(int $sorting, array $where): void
    {
        $sortField = $this->getTableName() . '.' . $this->sortingField();
        $where[$sortField . ' >= ?'] = $sorting;
        $this->findBy($where)->update([$this->sortingField() . '+=' => $this->sortingStep()]);
    }

    private function getRecordForShifting($record, array $where = []): ?Entity
    {
        if (!$record instanceof Entity) {
            $record = $this->find($record);
            if ($record === null) {
                return null;
            }
        }

        $sortField = $this->getTableName() . '.' . $this->sortingField();
        $equals = $this->query()
            ->select($sortField)
            ->where($where)
            ->group($sortField)
            ->having('COUNT(*) > 1')
            ->fetchPairs(null, $this->sortingField())
        ;

        if ($equals !== []) {
            $this->fixEqualSorting($where);

            $primary = $this->query()->getPrimary();
            return $this->find($record->$primary);
        }
        return $record;
    }

    private function fixEqualSorting(array $where = []): void
    {
        $sortField = $this->getTableName() . '.' . $this->sortingField();
        $sortingStep = $this->sortingStep();

        $sorting = 0;
        $query = $this->findBy($where)->order(implode(',', [$sortField, $this->query()->getPrimary()]))->fetchAll();
        foreach ($query as $row) {
            $sorting += $sortingStep;
            $this->update($row, [$this->sortingField() => $sorting]);
        }
    }
}
