<?php

namespace Efabrica\NetteRepository\Repository;

use Nette\Database\Table\GroupedSelection;
use Nette\Database\Table\Selection;

final class GroupedQuery extends GroupedSelection implements QueryInterface
{
    use QueryTrait;

    private Query $query;

    private function __construct(
        string $tableName,
        string $column,
        Selection $refTable,
        Query $query
    ) {
        $explorer = $query->getRepository()->getExplorer();
        parent::__construct($explorer, $explorer->getConventions(), $tableName, $column, $refTable);
        $this->query = $query;
        $this->repository = $query->getRepository();
        $this->behaviors = clone $query->getBehaviors();
    }

    public static function fromQuery(Query $query, string $tableName, string $column): self
    {
        return new self($tableName, $column, $query, $query);
    }

    public static function fromGroupedQuery(GroupedQuery $query, string $tableName, string $column): self
    {
        return new self($tableName, $column, $query, $query->query);
    }

    public function createSelectionInstance(?string $table = null): Query
    {
        if ($table === null) {
            return $this->repository->query();
        }
        return $this->repository->getManager()->byTableName($table)->query()->withScope($this->behaviors->getScope());
    }

    public function createGroupedSelectionInstance(string $table, string $column): self
    {
        return self::fromGroupedQuery($this, $table, $column);
    }
}
