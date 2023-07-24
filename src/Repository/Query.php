<?php

namespace Efabrica\NetteDatabaseRepository\Repository;

use Efabrica\NetteDatabaseRepository\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Event\SelectQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Subscriber\RepositoryEvents;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Efabrica\Translatte\Helper\Arr;
use Generator;
use LogicException;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\Arrays;
use Traversable;

/**
 * @template E of Entity
 */
class Query extends Selection
{
    protected const CHUNK_SIZE = 100;

    private bool $doesEvents;

    private Repository $repository;

    private RepositoryEvents $events;
    private RepositoryBehaviors $behaviors;

    /**
     * @param Repository<E,$this> $repository
     * @param bool                $events
     */
    public function __construct(Repository $repository, bool $events = true)
    {
        $this->repository = $repository;
        $this->doesEvents = $events;
        $this->events = clone $repository->getEvents();
        $this->behaviors = clone $repository->behaviors();
        parent::__construct($repository->getExplorer(), $repository->getExplorer()->getConventions(), $repository->getTableName());
    }

    /************************** Modifications *****************************/

    /**
     * @param E[]|E|array $data Supports multi-insert
     * @return E|int
     */
    public function insert(iterable $data)
    {
        if (!$this->doesEvents()) {
            if (Arrays::isList($data) && count($data) === 1) {
                $data = reset($data);
            }
            return parent::insert($data);
        }
        if (is_array($data)) {
            if (Arrays::isList($data)) {
                $data = array_map(fn($row) => $row instanceof Entity ? $row : $this->repository->createRow($row), $data);
            } else {
                $data = [$this->repository->createRow($data)];
            }
        } elseif ($data instanceof Entity) {
            $data = [$data];
        }
        return (new InsertRepositoryEvent($this->repository, $data))->handle()->getReturn();
    }

    public function update(iterable $data): int
    {
        if (!$this->doesEvents()) {
            return parent::update($data);
        }
        $data = $data instanceof Traversable ? iterator_to_array($data) : $data;
        return (new UpdateQueryEvent($this))->handle($data);
    }

    public function delete(): int
    {
        if (!$this->doesEvents()) {
            return parent::delete();
        }
        return (new DeleteQueryEvent($this))->handle();
    }

    protected function execute(): void
    {
        if ($this->rows === null && $this->doesEvents()) {
            (new SelectQueryEvent($this))->handle();
        }
        parent::execute();
    }

    /********************************** Events ***************************/

    /**
     * @param class-string<EventSubscriber> ...$eventClasses
     * @return Query cloned instance
     */
    public function withoutEvent(string ...$eventClasses): self
    {
        $clone = clone $this;
        foreach ($eventClasses as $eventClass) {
            $clone->events->removeEvent($eventClass);
        }
        return $clone;
    }

    public function withoutEvents(): self
    {
        $clone = clone $this;
        $clone->doesEvents = false;
        return $clone;
    }

    /********************************** Getters **************************/
    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getEvents(): RepositoryEvents
    {
        return $this->events;
    }

    public function getBehaviors(): RepositoryBehaviors
    {
        return $this->behaviors;
    }

    public function doesEvents(): bool
    {
        return $this->doesEvents;
    }

    protected function createRow(array $row = []): Entity
    {
        return $this->repository->createRow($row, $this);
    }

    public function createSelectionInstance(?string $table = null): self
    {
        return new static($this->repository, $this->doesEvents);
    }

    /**
     * @param array|string|ActiveRow $condition
     * @param mixed                  ...$params
     * @return $this
     */
    public function where($condition, ...$params): self
    {
        if ($condition instanceof ActiveRow) {
            return $this->wherePrimary($condition->getPrimary());
        }
        parent::where($condition, ...$params);
        return $this;
    }

    /**
     * @param ActiveRow|array|scalar ...$entities primary value, ActiveRow or associative array of primary values
     * @return $this
     */
    public function whereRows(iterable ...$entities): self
    {
        $where = [];
        $values = [];
        $primary = $this->repository->getPrimary();
        if ($primary === []) {
            throw new LogicException('Primary key is not set');
        }
        if (count($primary) === 1) {
            $primaryKey = reset($primary);
            foreach ($entities as $entity) {
                $values[] = is_scalar($entity) ? $entity : $entity[$primaryKey];
            }
            return $this->where($primaryKey, $values);
        }
        foreach ($entities as $row) {
            $key = [];
            foreach ($primary as $i => $primaryKey) {
                $key[] = $primaryKey . ' = ?';
                $value = $row[$primaryKey] ?? $row[$i] ?? null;
                if ($value === null) {
                    throw new LogicException("Primary key value for $primaryKey is not set");
                }
                $values[] = $value;
            }
            $where[] = implode(' AND ', $key);
        }
        parent::where(implode(' OR ', $where), ...$values);
        return $this;
    }

    public function search(array $columns, string $search): self
    {
        $where = [];
        $values = [];
        foreach ($columns as $column) {
            $where[] = $column . ' LIKE ?';
            $values[] = "%$search%";
        }
        parent::where('(' . implode(') OR (', $where) . ')', ...$values);
        return $this;
    }

    /**
     * @return E|null
     */
    public function fetch(): ?Entity
    {
        /** @var E|null $entity */
        $entity = parent::fetch();
        return $entity;
    }

    /**
     * @return E[]
     */
    public function fetchAll(): array
    {
        /** @var E[] $rows */
        $rows = parent::fetchAll();
        return $rows;
    }

    /**
     * @param int $chunkSize
     * @return Generator<E> foreach($query->fetchAllChunked() as $entity) { $entity->update(...); }
     */
    public function fetchChunked(int $chunkSize = self::CHUNK_SIZE): Generator
    {
        foreach ($this->chunks($chunkSize) as $chunk) {
            yield from $chunk;
        }
    }

    /**
     * @param int $chunkSize
     * @return Generator<self> foreach($query->chunks() as $chunk) { $chunk->fetchAll()->doSomething(); }
     */
    public function chunks(int $chunkSize = self::CHUNK_SIZE): Generator
    {
        $limit = $this->sqlBuilder->getLimit();
        $offset = $this->sqlBuilder->getOffset();
        $chunk = (clone $this)->page($offset, $chunkSize);
        while (true) {
            yield $chunk;
            if (count($chunk->fetchAll()) < $chunkSize) {
                break;
            }
            $offset += $chunkSize;
            if ($limit > 0 && $offset > $limit) {
                break;
            }
            $chunk = (clone $this)->limit($chunkSize, $offset);
        }
    }

    public function count(?string $column = null): int
    {
        if ($column === null && $this->rows === null) {
            $column = '*';
        }
        return parent::count($column);
    }
}
