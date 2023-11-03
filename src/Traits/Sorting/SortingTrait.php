<?php

namespace Efabrica\NetteRepository\Traits\Sorting;

use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Repository;

/**
 * @implements SortingInterface
 * @mixin Repository
 */
trait SortingTrait
{
    public function getSorting(): SortingBehavior
    {
        return $this->getBehaviors()->get(SortingBehavior::class);
    }

    public function moveUp($record, array $where = [], bool $up = true): bool
    {
        $record = $this->getRecordForShifting($record, $where);
        if ($record === null) {
            return false;
        }

        $behavior = $this->getSorting();
        $sortField = $this->getTableName() . '.' . $behavior->getColumn();
        $where[$sortField . ($up ? ' < ?' : ' > ?')] = $record[($behavior->getColumn())];

        $upperRecord = $this->findBy($where)->order($sortField . ($up ? ' DESC' : ' ASC'))->limit(1)->fetch();
        if (!$upperRecord) {
            return false;
        }

        $upperSorting = $upperRecord[($behavior->getColumn())];
        $upperRecord[($behavior->getColumn())] = $record[($behavior->getColumn())];
        $record[($behavior->getColumn())] = $upperSorting;
        $this->updateEntities($upperRecord, $record);
        return true;
    }

    public function moveDown($record, array $where = []): bool
    {
        return $this->moveUp($record, $where, false);
    }

    public function insertAfter(int $sorting, array $where): void
    {
        $behavior = $this->getSorting();
        $sortField = $this->getTableName() . '.' . $behavior->getColumn();
        $where[$sortField . ' <= ?'] = $sorting;
        $this->findBy($where)->update([$behavior->getColumn() . '-=' => $behavior->getStep()]);
    }

    public function insertBefore(int $sorting, array $where): void
    {
        $behavior = $this->getSorting();
        $sortField = $this->getTableName() . '.' . $behavior->getColumn();
        $where[$sortField . ' >= ?'] = $sorting;
        $this->findBy($where)->update([$behavior->getColumn() . '+=' => $behavior->getStep()]);
    }

    private function getRecordForShifting($record, array $where = []): ?Entity
    {
        if (!$record instanceof Entity) {
            $record = $this->find($record);
            if ($record === null) {
                return null;
            }
        }

        $behavior = $this->getSorting();
        $sortField = $this->getTableName() . '.' . $behavior->getColumn();
        $equals = $this->query()
            ->select($sortField)
            ->where($where)
            ->group($sortField)
            ->having('COUNT(*) > 1')
            ->fetchPairs(null, $behavior->getColumn())
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
        $behavior = $this->getSorting();
        $sortField = $this->getTableName() . '.' . $behavior->getColumn();
        $sortingStep = $behavior->getStep();

        $sorting = 0;
        $query = $this->findBy($where)->order(implode(',', [$sortField, $this->query()->getPrimary()]))->fetchAll();
        foreach ($query as $row) {
            $sorting += $sortingStep;
            $this->update($row, [$behavior->getColumn() => $sorting]);
        }
    }
}
