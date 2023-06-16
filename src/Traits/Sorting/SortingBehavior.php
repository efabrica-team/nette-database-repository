<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Sorting;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Repository;

/**
 * @mixin Repository
 */
trait SortingBehavior
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

        $sortField = $this->tableName . '.' . $this->sortingField();
        $where[$sortField . ($up ? ' < ?' : ' > ?')] = $record[$this->sortingField()];

        $upperRecord = $this->findBy($where)->order($sortField . ' DESC')->limit(1)->fetch();
        if (!$upperRecord) {
            return false;
        }

        $upperSorting = $upperRecord[$this->sortingField()];
        $upperRecord[$this->sortingField()] = $record[$this->sortingField()];
        $record[$this->sortingField()] = $upperSorting;
        $this->update($upperRecord);
        $this->update($record);
        return true;
    }

    public function moveDown($record, array $where = []): bool
    {
        return $this->moveUp($record, $where, false);
    }

    public function insertAfter(int $sorting, array $where): void
    {
        $sortField = $this->tableName . '.' . $this->sortingField();
        $where[$sortField . ' <= ?'] = $sorting;
        $this->findBy($where)->update([$this->sortingField() . '-=' => $this->sortingStep()]);
    }

    public function insertBefore(int $sorting, array $where): void
    {
        $sortField = $this->tableName . '.' . $this->sortingField();
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

        $sortField = $this->tableName . '.' . $this->sortingField();
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
        $sortField = $this->tableName . '.' . $this->sortingField();
        $sortingStep = $this->sortingStep();

        $sorting = 0;
        $query = $this->findBy($where)->order(implode(',', [$sortField, $this->query()->getPrimary()]))->fetchAll();
        foreach ($query as $row) {
            $sorting += $sortingStep;
            $row[$this->sortingField()] = $sorting;
            $this->update($row);
        }
    }
}
