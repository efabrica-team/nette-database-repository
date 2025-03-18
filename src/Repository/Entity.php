<?php

namespace Efabrica\NetteRepository\Repository;

use ArrayIterator;
use DateTimeInterface;
use Efabrica\NetteRepository\Repository\Scope\FullScope;
use Efabrica\NetteRepository\Repository\Scope\RawScope;
use Efabrica\NetteRepository\Repository\Scope\Scope;
use Efabrica\NetteRepository\Traits\RelatedThrough\GetRelatedQueryEvent;
use Efabrica\NetteRepository\Traits\RelatedThrough\SetRelatedRepositoryEvent;
use Iterator;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\GroupedSelection;
use Nette\Database\Table\Selection;
use Nette\InvalidStateException;

abstract class Entity extends ActiveRow
{
    private array $_unsavedChanges = [];

    private QueryInterface $_query;

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
            unset($this->_unsavedChanges[$key]);
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
     * @param iterable $data Additional data to fill the entity with before saving
     * @return $this
     */
    public function save(iterable $data = []): self
    {
        $this->fill($data);
        $query = $this->_query->createSelectionInstance();
        // if entity is new, insert it
        if ($this->internalData() === []) {
            $insert = $query->insert($this->_unsavedChanges);
            if ($insert instanceof self) {
                $this->internalData($insert->toArray(), false);
            }
        } elseif ($this->_unsavedChanges !== []) {
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
        $result = $this->_query->createSelectionInstance()->update($this->_unsavedChanges, [$this]);
        $this->_unsavedChanges = [];
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
        return isset($this->_unsavedChanges[$key]) || parent::__isset($key);
    }

    public function &__get(string $key): mixed
    {
        if (array_key_exists($key, $this->_unsavedChanges)) {
            return $this->_unsavedChanges[$key];
        }
        /** @var mixed $value */
        $value = parent::__get($key);
        return $value;
    }

    /**
     * @param string $column
     * @param mixed $value
     */
    public function __set($column, $value): void
    {
        if (parent::__isset($column)) {
            if (self::isSameValue(parent::__get($column), $value)) {
                unset($this->_unsavedChanges[$column]);
            } else {
                $this->_unsavedChanges[$column] = $value;
            }
        } elseif ($value === null) {
            unset($this->_unsavedChanges[$column]);
        } else {
            $this->_unsavedChanges[$column] = $value;
        }
    }

    /**
     * @param string $key
     */
    public function __unset($key): void
    {
        $this->$key = null;
    }

    public function toArray(): array
    {
        return $this->_unsavedChanges + parent::toArray();
    }

    public function toOriginalArray(): array
    {
        return parent::toArray();
    }

    public function unsavedChanges(): array
    {
        return $this->_unsavedChanges;
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
     * @param class-string<Repository> $throughRepoClass
     * @param class-string<Repository> $otherRepoClass
     * @param literal-string $selfColumn
     * @param literal-string $otherColumn
     * @return Query
     */
    public function relatedThrough(string $throughRepoClass, string $otherRepoClass, string $selfColumn, string $otherColumn): Query
    {
        $throughRepo = $this->getManager()->byClass($throughRepoClass);
        $otherRepo = $this->getManager()->byClass($otherRepoClass);
        return (new GetRelatedQueryEvent($this, $throughRepo, $otherRepo, $selfColumn, $otherColumn))->handle()->getQuery();
    }

    /**
     * @param class-string<Repository> $throughRepoClass
     * @param literal-string $selfColumn
     * @param literal-string $otherColumn
     * @param iterable<Entity|int|string> $owned
     * @return $this
     */
    public function setRelatedThrough(string $throughRepoClass, string $selfColumn, string $otherColumn, iterable $owned): self
    {
        $throughRepo = $this->getManager()->byClass($throughRepoClass);
        $event = new SetRelatedRepositoryEvent($throughRepo, $this, $owned, $selfColumn, $otherColumn);
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
    public static function isSameValue($a, $b): bool
    {
        return static::normalizeValue($a) === static::normalizeValue($b);
    }

    /**
     * @param mixed $a
     * @return mixed
     */
    protected static function normalizeValue($a)
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

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->toArray());
    }

    /**
     * @param bool $throw true = throw exception if primary is not set, false = return null if primary is not set
     * @param bool $original true = do not include unsaved changes, false = include unsaved primary key changes
     * @return int|string|(int|string)[]|DateTimeInterface|null primary key value
     */
    public function getPrimary(bool $throw = true, bool $original = true): mixed
    {
        $primary = $this->_query->getPrimary($throw);
        if ($primary === null) {
            return null;
        }
        $self = $this;
        if ($original) {
            $self = $this->toOriginalArray();
        }
        if (!is_array($primary)) {
            if (isset($self[$primary])) {
                return $self[$primary];
            }
            if ($throw) {
                throw new InvalidStateException("Row does not contain primary $primary column data.");
            }
            return null;
        }

        $primaryVal = [];
        foreach ($primary as $key) {
            if (isset($self[$key])) {
                $primaryVal[$key] = $self[$key];
            } else {
                if ($throw) {
                    throw new InvalidStateException("Row does not contain primary $key column data.");
                }
                return null;
            }
        }

        return $primaryVal;
    }

    /**
     * @param bool $throw true = throw exception if primary is not set, false = return null if primary is not set
     * @param bool $original true = do not include unsaved changes, false = include unsaved primary key changes
     * @return string of primary key values joined by |
     */
    public function getSignature(bool $throw = true, bool $original = true): string
    {
        return implode('|', (array)$this->getPrimary($throw, $original));
    }
}
