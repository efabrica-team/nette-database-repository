<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Choozer;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Query;
use Efabrica\NetteDatabaseRepository\Repository\Repository;

/**
 * @mixin Repository
 */
trait ChoozerBehavior
{
    abstract protected function choozerFieldsToSearchIn(): array;

    protected function choozerPrefetchField(): string
    {
        return 'id';
    }

    abstract protected function choozerOrder(): string;

    protected function choozerSearchFunction(): string
    {
        return 'LIKE';
    }

    protected function choozerSearchPattern(): string
    {
        return '%%%s%%';
    }

    public function countBySearchString(string $searchString): int
    {
        $where = $this->createWhereForSearchString($searchString);
        return $this->query()->where($where)->count('*');
    }

    public function findBySearchString(string $searchString, int $page = 1, int $limit = 30): Query
    {
        $where = $this->createWhereForSearchString($searchString);
        return $this->query()->where($where)->order($this->choozerOrder())->limit($limit, ($page - 1) * $limit);
    }

    /**
     * @param array $ids
     * @return Entity[]
     */
    public function prefetch(array $ids): array
    {
        $items = $this->findBy([$this->choozerPrefetchField() => $ids])->fetchPairs($this->choozerPrefetchField());
        return array_filter(array_replace(array_flip($ids), $items), static fn($item) => $item instanceof Entity);
    }

    private function createWhereForSearchString(?string $searchString): array
    {
        $where = [];
        if (!$searchString) {
            return $where;
        }
        $whereKeyParts = [];
        $whereValuesParts = [];
        foreach ($this->choozerFieldsToSearchIn() as $field) {
            $whereKeyParts[] = $field . ' ' . $this->choozerSearchFunction() . ' ?';
            $whereValuesParts[] = sprintf($this->choozerSearchPattern(), $searchString);
        }

        // FIX: Column operator does not accept array argument.
        if (count($whereKeyParts) === 1) {
            $where[$whereKeyParts[0]] = $whereValuesParts[0];
            return $where;
        }

        $where[implode(' OR ', $whereKeyParts)] = $whereValuesParts;
        return $where;
    }
}
