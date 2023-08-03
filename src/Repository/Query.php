<?php

namespace Efabrica\NetteRepository\Repository;

use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Scope\Scope;
use Nette\Database\Table\GroupedSelection;
use Nette\Database\Table\Selection;

/**
 * @template E of Entity
 */
class Query extends Selection
{
    use QueryTrait;

    protected const CHUNK_SIZE = 127;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->events = clone $repository->getEvents();
        $this->behaviors = clone $repository->behaviors();
        parent::__construct($repository->getExplorer(), $repository->getExplorer()->getConventions(), $repository->getTableName());
    }

    public function createSelectionInstance(?string $table = null): self
    {
        if ($table === null) {
            return new (static::class)($this->repository);
        }
        return $this->repository->getManager()->byTableName($table)->query()->setScope($this->scope);
    }

    public function createGroupedSelectionInstance(string $table, string $column): GroupedSelection
    {
        return GroupedQuery::fromQuery($this, $table, $column);
    }
}
