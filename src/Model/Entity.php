<?php

namespace Efabrica\NetteRepository\Model;

use ArrayIterator;
use DateTimeInterface;
use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Repository\QueryInterface;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Repository\RepositoryManager;
use Efabrica\NetteRepository\Repository\Scope\FullScope;
use Efabrica\NetteRepository\Repository\Scope\RawScope;
use Efabrica\NetteRepository\Repository\Scope\Scope;
use Efabrica\NetteRepository\Traits\RelatedThrough\GetRelatedThroughQueryEvent;
use Efabrica\NetteRepository\Traits\RelatedThrough\SetRelatedThroughRepositoryEvent;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\GroupedSelection;
use Nette\Database\Table\Selection;
use ReflectionProperty;

abstract class Entity extends ActiveRow
{
    protected array $_modified = [];

    private QueryInterface $_query;

    private static ReflectionProperty $data;

    public function __construct(array $data, QueryInterface $query)
    {
        /** @var QueryInterface&Selection $query */
        parent::__construct($data, $query);
        $this->_query = $query;
    }

    /**
     * @internal
     */
    public function internalData(iterable $data = [], bool $merge = true): array
    {
        $newData = $merge ? (((array)$this)["\x00" . ActiveRow::class . "\x00data"] ?? []) : [];
        foreach ($data as $key => $value) {
            $newData[$key] = $value;
            unset($this->_modified[$key]);
        }
        if (!$merge || $data !== []) {
            array_walk($this, static fn(&$value, $key) => str_ends_with((string)$key, "\x00data") ? $value = $newData : null);
        }
        return $newData;
    }

    /**
     * @param iterable $data
     * @return self&$this
     */
    public function fill(iterable $data): self
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        return $this;
    }

    /**
     * Sync state of entity into database
     * @return $this
     */
    public function save(): self
    {
        $query = $this->_query->createSelectionInstance();
        // if entity is new, insert it
        if ($this->internalData() === []) {
            $insert = $query->insert($this->_modified);
            if ($insert instanceof self) {
                $this->internalData($insert->toArray(), false);
            }
        } else {
            $this->update();
        }
        return $this;
    }

    /**
     * @param array<static::*,mixed> $data
     * @return bool
     */
    public function update(iterable $data = []): bool
    {
        $this->fill($data);
        $result = $this->_query->createSelectionInstance()->update($this->_modified, [$this]);
        $this->_modified = [];
        return (bool)$result;
    }

    public function delete(): int
    {
        return $this->_query->createSelectionInstance()->delete([$this]);
    }

    /**
     * @param mixed $key
     */
    public function __isset($key): bool
    {
        return isset($this->_modified[$key]) || parent::__isset($key);
    }

    public function &__get(string $key)
    {
        if (array_key_exists($key, $this->_modified)) {
            return $this->_modified[$key];
        }
        /** @var mixed $value */
        $value = parent::__get($key);
        return $value;
    }

    /**
     * @param string $column
     * @param mixed  $value
     */
    public function __set($column, $value): void
    {
        if (parent::__isset($column)) {
            if ($this->isSameValue(parent::__get($column), $value)) {
                unset($this->_modified[$column]);
            } else {
                $this->_modified[$column] = $value;
            }
        } elseif ($value === null) {
            unset($this->_modified[$column]);
        } else {
            $this->_modified[$column] = $value;
        }
    }

    /**
     * @param string $key
     */
    public function __unset($key)
    {
        $this->_modified[$key] = null;
    }

    public function toArray(): array
    {
        return $this->_modified + parent::toArray();
    }

    public function toOriginalArray(): array
    {
        return parent::toArray();
    }

    public function diff(): array
    {
        return $this->_modified;
    }

    /**
     * @internal Use typehinted Entity getters instead
     */
    public function ref(string $key, ?string $throughColumn = null): ?ActiveRow
    {
        return parent::ref($key, $throughColumn);
    }

    /**
     * @internal Use typehinted $otherRepo->findBy($throughColumn, $row[$key]) instead
     */
    public function related(string $key, ?string $throughColumn = null): GroupedSelection
    {
        return parent::related($key, $throughColumn);
    }

    public function relatedThrough(string $throughRepoClass, string $otherRepoClass, string $selfColumn, string $otherColumn): Query
    {
        $throughRepo = $this->getManager()->byClass($throughRepoClass);
        $otherRepo = $this->getManager()->byClass($otherRepoClass);
        return (new GetRelatedThroughQueryEvent($this, $throughRepo, $otherRepo, $selfColumn, $otherColumn))->handle();
    }

    public function setRelatedThrough(string $throughRepoClass, string $selfColumn, string $otherColumn, iterable $owned): self
    {
        $throughRepo = $this->getManager()->byClass($throughRepoClass);
        $event = new SetRelatedThroughRepositoryEvent($throughRepo, $this, $owned, $selfColumn, $otherColumn);
        $event->handle();
        return $this;
    }

    /**
     * @param class-string<Repository> $repository
     */
    protected function query(string $repository): Query
    {
        return $this->getManager()->byClass($repository)->query();
    }

    protected function getManager(): RepositoryManager
    {
        return $this->_query->getRepository()->getManager();
    }

    public function getTableName(): string
    {
        return $this->_query->getName();
    }

    public function setScope(Scope $scope): self
    {
        $clone = clone $this;
        $clone->_query = (clone $clone->_query)->withScope($scope);
        return $clone;
    }

    public function scopeRaw(): self
    {
        return $this->setScope(new RawScope());
    }

    public function scopeFull(): self
    {
        return $this->setScope(new FullScope());
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @return bool
     */
    private function isSameValue($a, $b): bool
    {
        return $this->normalizeValue($a) === $this->normalizeValue($b);
    }

    private function normalizeValue($a)
    {
        if (is_bool($a)) {
            $a = $a ? 1 : 0;
        } elseif ($a instanceof DateTimeInterface) {
            $a = $a->format('c');
        } elseif (is_float($a)) {
            $a = rtrim(rtrim(number_format($a, 10, '.', ''), '0'), '.');
        }
        if (is_int($a)) {
            $a = (string)$a;
        }
        return $a;
    }

    public function getIterator(): \Iterator
    {
        return new ArrayIterator($this->_modified + parent::toArray());
    }
}
