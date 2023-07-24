<?php

namespace Efabrica\NetteDatabaseRepository\Model;

use Efabrica\NetteDatabaseRepository\Repository\Query;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\GroupedSelection;
use ReflectionProperty;

abstract class Entity extends ActiveRow
{
    protected array $_modified = [];

    private Query $_query;

    private static ReflectionProperty $data;

    public function __construct(array $data, Query $query)
    {
        parent::__construct($data, $query);
        $this->_query = $query;
    }

    /**
     * Sync state of entity into database
     * @param bool $events
     * @return bool
     */
    public function save(bool $events = true): bool
    {
        $query = $this->_query->getRepository()->query($events);
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
     * @param iterable<self::*,mixed> $data
     * @return bool
     */
    public function update(iterable $data = []): bool
    {
        $result = parent::update($data + $this->_modified);
        $this->_modified = [];
        return $result;
    }

    public function delete(bool $events = true): int
    {
        return $this->_query->getRepository()
            ->query($events)
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
     * @deprecated Do not use, use $this->query($repoClass)->where($key, $this->getPrimary())->fetch()
     */
    public function ref(string $key, ?string $throughColumn = null): ?ActiveRow
    {
        return parent::ref($key, $throughColumn);
    }

    /**
     * @deprecated Do not use, use $this->query($repoClass)->where($key, $this->getPrimary())->fetchAll()
     */
    public function related(string $key, ?string $throughColumn = null): GroupedSelection
    {
        return parent::related($key, $throughColumn);
    }

    /**
     * @param class-string<Repository> $repository
     * @return Query<Repository>
     */
    protected function query(string $repository, bool $events = true): Query
    {
        return $this->_query->getRepository()->getManager()
            ->getRepository($repository)->query($events)
        ;
    }

    public function getTableName(): string
    {
        return $this->_query->getName();
    }
}
