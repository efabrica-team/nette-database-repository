<?php

namespace Efabrica\NetteRepository\Repository;

use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\Arrays;

/**
 * @template E of Entity
 */
class Query extends Selection implements QueryInterface
{
    use QueryTrait;

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

    public function wherePrimary(mixed $key): static
    {
        if (is_array($this->primary) && Arrays::isList($key)) {
            if (isset($key[0]) && is_array($key[0])) {
                $this->addPrimaryWhere($key);
            } else {
                foreach ($this->primary as $i => $primary) {
                    $this->where($this->name . '.' . $primary, $key[$i]);
                }
            }
        } elseif (is_array($key) && !Arrays::isList($key)) { // key contains column names
            foreach ($key as $columnName => $value) {
                if (!str_contains($columnName, '.')) {
                    $columnName = $this->name . '.' . $columnName;
                }
                $this->where([$columnName => $value]);
            }
        } else {
            $this->addPrimaryWhere($key);
        }

        return $this;
    }

    public function getReferencedTable(ActiveRow $row, ?string $table, ?string $column = null): ActiveRow|false|null
    {
        if ($column === null && $table !== null) {
            $belongsTo = $this->conventions->getBelongsToReference($this->name, $table);
            if ($belongsTo === null) {
                return false;
            }

            [$table, $column] = $belongsTo;
        }

        if (!$row->accessColumn($column)) {
            return false;
        }

        $checkPrimaryKey = $row[$column];

        /** @var array<string, mixed> $referenced */
        $referenced = &$this->refCache['referenced'][$this->getSpecificCacheKey()]["$table.$column"];
        $selection = &$referenced['selection'];
        $cacheKeys = &$referenced['cacheKeys'];
        if ($selection === null || ($checkPrimaryKey !== null && !isset($cacheKeys[$checkPrimaryKey]))) {
            $this->execute();
            $cacheKeys = [];
            foreach ($this->rows ?? [] as $sRow) {
                if ($sRow[$column] === null) {
                    continue;
                }
                $key = $sRow[$column];
                $cacheKeys[$key] = true;
            }

            if ($cacheKeys) {
                $selection = $this->createSelectionInstance($table);
                $selection->where($this->getPrefixedPrimary($selection), array_keys($cacheKeys));
            } else {
                $selection = [];
            }
        }

        return $selection[$checkPrimaryKey ?? ''] ?? null;
    }

    private function addPrimaryWhere(mixed $key): void
    {
        if (is_string($this->primary)) {
            $this->where($this->name . '.' . $this->primary, $key);
        }

        if (is_array($this->primary)) {
            $primaries = array_map(fn(string $column) => $this->name . '.' . $column, $this->primary);
            $this->where($primaries, $key);
        }
    }

    /**
     * @return string|string[]
     */
    private function getPrefixedPrimary(Selection $selection): array|string
    {
        /** @var string|string[] $primary */
        $primary = $selection->getPrimary();
        if (is_string($primary)) {
            return $selection->getName() . '.' . $primary;
        }
        return array_map(fn($key) => $selection->getName() . '.' . $key, $primary);
    }

}
