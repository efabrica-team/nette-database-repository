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

    public function __construct(Repository $repository, bool $events = true, ?Scope $scope = null)
    {
        $this->repository = $repository;
        $this->doesEvents = $events;
        $this->events = clone $repository->getEvents();
        $this->behaviors = clone $repository->behaviors();
        parent::__construct($repository->getExplorer(), $repository->getExplorer()->getConventions(), $repository->getTableName());
        $this->scope = $scope ?? $repository->getScope();
    }

    public function createSelectionInstance(?string $table = null): self
    {
        return new (static::class)($this->repository, $this->doesEvents);
    }

    public function createGroupedSelectionInstance(string $table, string $column): GroupedSelection
    {
        return GroupedQuery::fromQuery($this, $table, $column);
    }
}
