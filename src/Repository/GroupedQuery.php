<?php

namespace Efabrica\NetteRepository\Repository;

use Nette\Database\Explorer;
use Nette\Database\Table\GroupedSelection;
use Nette\Database\Table\Selection;

class GroupedQuery extends GroupedSelection
{
    use QueryTrait;

    private Query $query;

    private function __construct(
        Explorer $explorer,
        string $tableName,
        string $column,
        Selection $refTable
    ) {
        parent::__construct($explorer, $explorer->getConventions(), $tableName, $column, $refTable);
    }

    public static function fromQuery(Query $query, string $tableName, string $column): self
    {
        $g = new self($query->getRepository()->getExplorer(), $tableName, $column, $query);
        $g->query = $query;
        $g->repository = $query->getRepository();
        $g->events = clone $query->getEvents();
        $g->behaviors = clone $query->getBehaviors();
        $g->doesEvents = $query->doesEvents();
        return $g;
    }

    public static function fromGroupedQuery(GroupedQuery $query, string $tableName, string $column): self
    {
        $g = new self($query->getExplorer(), $tableName, $column, $query);
        $g->query = $query->query;
        $g->repository = $query->getRepository();
        $g->events = clone $query->getEvents();
        $g->behaviors = clone $query->getBehaviors();
        $g->doesEvents = $query->doesEvents();
        return $g;
    }

    public function createSelectionInstance(?string $table = null): Query
    {
        return new (get_class($this->query))($this->repository, $this->doesEvents);
    }

    public function createGroupedSelectionInstance(string $table, string $column): self
    {
        return static::fromGroupedQuery($this, $table, $column);
    }
}
