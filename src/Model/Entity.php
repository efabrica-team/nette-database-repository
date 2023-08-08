<?php

namespace Efabrica\NetteRepository\Model;

use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Repository\QueryInterface;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Repository\Scope\FullScope;
use Efabrica\NetteRepository\Repository\Scope\RawScope;
use Efabrica\NetteRepository\Repository\Scope\Scope;
use Efabrica\NetteRepository\Repository\Scope\ScopeContainer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\GroupedSelection;
use Nette\Database\Table\Selection;
use ReflectionProperty;
use Traversable;

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
     * Sync state of entity into database
     * @return bool
     */
    public function save(): bool
    {
        $query = $this->_query->getRepository()->query();
        if (!isset(self::$data)) {
            self::$data = new ReflectionProperty(ActiveRow::class, 'data');
            self::$data->setAccessible(true);
        }

        // if entity is new, insert it
        if (self::$data->getValue($this) === []) {
            $insert = $query->insert($this->_modified);
            if ($insert instanceof ActiveRow) {
                self::$data->setValue($this, $insert->toArray());
                $this->_modified = [];
            }
            return $insert !== null;
        }
        return $this->update() > 0;
    }

    /**
     * @param array<static::*,mixed> $data
     * @return bool
     */
    public function update(iterable $data = []): bool
    {
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        $result = parent::update($data + $this->_modified);
        $this->_modified = [];
        return $result;
    }

    public function delete(): int
    {
        return $this->_query->getRepository()
            ->query()
            ->wherePrimary($this->getPrimary())
            ->delete()
        ;
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
        if (parent::__isset($column) && parent::__get($column) === $value) {
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

    /**
     * @param class-string<Repository> $repository
     */
    protected function query(string $repository): Query
    {
        return $this->_query->getRepository()->getManager()
            ->byClass($repository)->query()
        ;
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
}
