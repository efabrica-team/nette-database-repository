<?php

namespace Efabrica\NetteRepository\Repository;

use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\SelectQueryEvent;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Scope\FullScope;
use Efabrica\NetteRepository\Repository\Scope\RawScope;
use Efabrica\NetteRepository\Repository\Scope\Scope;
use Efabrica\NetteRepository\Subscriber\RepositoryEvents;
use Generator;
use LogicException;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Arrays;
use Traversable;

/**
 * @template E of Entity
 * @method $this limit(int $limit, ?int $offset = null)
 */
trait QueryTrait
{
    protected Repository $repository;

    protected RepositoryBehaviors $behaviors;

    public function insert(iterable $data)
    {
        if (!$this->doesEvents()) {
            if (Arrays::isList($data) && is_countable($data) && count($data) === 1) {
                $data = reset($data);
            }
            return parent::insert($data);
        }
        if (is_array($data)) {
            if (Arrays::isList($data)) {
                $data = array_map(fn($row) => $row instanceof Entity ? $row :
                    $this->repository->createRow($row, $this), $data);
            } else {
                $data = [$this->repository->createRow($data, $this)];
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

    /**
     * @param iterable<Entity>|null $entities
     */
    public function delete(?iterable $entities = null): int
    {
        if (!$this->doesEvents()) {
            return parent::delete();
        }
        if ($entities !== null) {
            $this->whereRows(...$entities);
        }
        return (new DeleteQueryEvent($this, $entities))->handle();
    }

    protected function execute(): void
    {
        if ($this->rows === null && $this->doesEvents()) {
            (new SelectQueryEvent($this))->handle();
        }
        parent::execute();
    }

    /**
     * @param array|string|ActiveRow $condition
     * @param mixed                  ...$params
     * @return $this
     */
    public function where($condition, ...$params): self
    {
        if ($condition instanceof ActiveRow) {
            $this->wherePrimary($condition->getPrimary());
            return $this;
        }
        parent::where($condition, ...$params);
        return $this;
    }

    public function whereRows(...$entities): self
    {
        $where = $values = [];
        $primary = $this->getPrimary();
        if ($primary === null) {
            throw new LogicException('Primary key is not set');
        }
        if (is_string($primary)) {
            foreach ($entities as $entity) {
                $values[] = is_scalar($entity) ? $entity : $entity[$primary];
            }
            return $this->where($primary, $values);
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
    public function fetchChunked(int $chunkSize = Query::CHUNK_SIZE): Generator
    {
        foreach ($this->chunks($chunkSize) as $chunk) {
            /** @var Traversable<E> $chunk */
            yield from $chunk;
        }
    }

    /**
     * @param int $chunkSize
     * @return Generator<static> foreach($query->chunks() as $chunk) { $chunk->fetchAll()->doSomething(); }
     */
    public function chunks(int $chunkSize = Query::CHUNK_SIZE): Generator
    {
        $limit = $this->sqlBuilder->getLimit();
        if ($limit < 1) {
            $limit = PHP_INT_MAX;
        }
        $offset = $this->sqlBuilder->getOffset() ?? 0;
        $chunk = (clone $this)->limit(min($chunkSize, $limit), $offset);
        while (true) {
            yield $chunk;
            if (count($chunk->fetchAll()) < $chunkSize) {
                break;
            }
            $offset += $chunkSize;
            if ($offset > $limit) {
                break;
            }
            $chunk = (clone $this)->limit(min($chunkSize, $limit - $offset), $offset);
        }
    }

    public function count(?string $column = null): int
    {
        if ($column === null && $this->rows === null) {
            $column = '*';
        }
        return parent::count($column);
    }

    /*********************** Getters *************************/

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getEvents(): RepositoryEvents
    {
        return $this->repository->getEvents();
    }

    public function getBehaviors(): RepositoryBehaviors
    {
        return $this->behaviors;
    }

    protected function doesEvents(): bool
    {
        return $this->behaviors->isScope(RawScope::class);
    }

    protected function createRow(array $row = []): Entity
    {
        return $this->repository->createRow($row, $this);
    }

    public function getScope(): Scope
    {
        return $this->behaviors->getScope();
    }

    public function withScope(Scope $scope): self
    {
        $clone = clone $this;
        $clone->behaviors = $this->behaviors->withScope($scope);
        return $clone;
    }

    public function scopeRaw(): self
    {
        return $this->withScope(new RawScope());
    }

    public function scopeFull(): self
    {
        return $this->withScope(new FullScope());
    }
}
