<?php

namespace Efabrica\NetteDatabaseRepository\Model;

use Efabrica\NetteDatabaseRepository\Repository\Query;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\GroupedSelection;

abstract class Entity extends ActiveRow
{
    private array $_modified = [];

    private Query $query;

    public function __construct(array $data, Query $query)
    {
        parent::__construct($data, $query);
        $this->query = $query;
    }

    public function diff(): array
    {
        return $this->_modified;
    }

    public function save(bool $events = true): bool
    {
        return $this->query->getRepository()
                ->query($events)
                ->wherePrimary($this->getPrimary())
                ->update($this->diff()) > 0;
    }

    /**
     * @deprecated call setters and use save() instead
     * @param iterable $data
     * @return bool
     */
    public function update(iterable $data = []): bool
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
        return $this->query->getRepository()->update($this) > 0;
    }

    public function delete(bool $events = true): int
    {
        return $this->query->getRepository()
            ->query($events)
            ->wherePrimary($this->getPrimary())
            ->delete();
    }

    /**
     * @param mixed $key
     */
    public function __isset($key): bool
    {
        return parent::__isset($key) || isset($this->_modified[$key]);
    }

    public function &__get(string $key)
    {
        return array_key_exists($key, $this->_modified) ? $this->_modified[$key] : parent::__get($key);
    }

    /**
     * @param string $column
     * @param mixed  $value
     */
    public function __set($column, $value): void
    {
        if (parent::__get($column) === $value) {
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
        unset($this->_modified[$key]);
    }

    public function toArray(): array
    {
        return $this->_modified + parent::toArray();
    }

    public function toOriginalArray(): array
    {
        return parent::toArray();
    }

    /**
     * @deprecated Do not use, call repositories directly.
     */
    public function ref(string $key, ?string $throughColumn = null): ?ActiveRow
    {
        return parent::ref($key, $throughColumn);
    }

    /**
     * @deprecated Do not use, call repositories directly.
     */
    public function related(string $key, ?string $throughColumn = null): GroupedSelection
    {
        return parent::related($key, $throughColumn);
    }

    public function getTableName(): string
    {
        return $this->query->getName();
    }
}
