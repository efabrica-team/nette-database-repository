<?php

namespace Efabrica\NetteRepository\Repository;

use Nette\Database\Table\Selection;

/**
 * @template E of Entity
 */
class Query extends Selection implements QueryInterface
{
    use QueryTrait;
    use QuerySelectionTrait;

    public const CHUNK_SIZE = 127;

    /**
     * @param Repository<E, Query<E>> $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->behaviors = clone $repository->getBehaviors();
        parent::__construct($repository->getExplorer(), $repository->getExplorer()->getConventions(), $repository->getTableName());
    }

    public function createSelectionInstance(?string $table = null): Query
    {
        if ($table === null) {
            return $this->repository->query();
        }
        return $this->repository->getManager()->byTableName($table)->query()->withScope($this->behaviors->getScope());
    }

    public function createGroupedSelectionInstance(string $table, string $column): GroupedQuery
    {
        return GroupedQuery::fromQuery($this, $table, $column);
    }
}
