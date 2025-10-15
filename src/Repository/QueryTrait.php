<?php

namespace Efabrica\NetteRepository\Repository;

use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\SelectQueryEvent;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Repository\Scope\FullScope;
use Efabrica\NetteRepository\Repository\Scope\RawScope;
use Efabrica\NetteRepository\Repository\Scope\Scope;
use Efabrica\NetteRepository\Subscriber\RepositoryEventSubscribers;
use Generator;
use LogicException;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Arrays;
use Traversable;

/**
 * @template E of Entity
 * @method $this limit(?int $limit, ?int $offset = null)
 */
trait QueryTrait
{
    protected Repository $repository;

    protected RepositoryBehaviors $behaviors;

    private array $entityState = [];

    public function insert(iterable $data): Entity|array|int|bool
    {
        if ($data === []) {
            return 0;
        }
        if (!$this->doesEvents()) {
            if (Arrays::isList($data) && is_countable($data) && count($data) === 1) {
                $data = reset($data);
            }
            return parent::insert($data);
        }
        if (is_array($data)) {
            if (Arrays::isList($data)) {
                $data = array_map(fn($row) => $row instanceof Entity ? $row :
                    $this->createRow($row), $data);
            } else {
                $data = [$this->createRow($data)];
            }
        } elseif ($data instanceof Entity) {
            $data = [$data];
        }
        return (new InsertRepositoryEvent($this->repository, $data))->handle()->getReturn();
    }

    /**
     * @param iterable   $data Data to update
     * @param array|null $entities Entities passed by reference to be updated
     * @return int Affected rows
     */
    public function update(iterable $data, ?array $entities = null): int
    {
        if ($entities !== null) {
            $this->whereEntities($entities);
        }
        if (!$this->doesEvents()) {
            return parent::update($data);
        }
        $data = $data instanceof Traversable ? iterator_to_array($data) : $data;
        foreach (($entities ?? []) as $entity) {
            if ($entity instanceof Entity) {
                $entity->fill($data);
            }
        }
        return (new UpdateQueryEvent($this, $entities))->handle($data)->getAffectedRows();
    }

    /**
     * @param Entity[]|null $entities
     */
    public function delete(?array $entities = null): int
    {
        if ($entities !== null) {
            $this->whereEntities($entities);
        }
        if (!$this->doesEvents()) {
            return parent::delete();
        }
        return (new DeleteQueryEvent($this, $entities))->handle()->getAffectedRows();
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
     */
    public function where($condition, ...$params): static
    {
        if ($condition instanceof ActiveRow) {
            $this->wherePrimary($condition->getPrimary());
            return $this;
        }
        parent::where($condition, ...$params);
        return $this;
    }

    /**
     * @param Entity[] $entities
     */
    public function whereEntities(array $entities, bool $original = true): static
    {
        $where = $values = [];
        $primary = $this->getPrimary();
        if ($primary === null) {
            throw new LogicException('Primary key is not set');
        }
        if (is_string($primary)) {
            foreach ($entities as $entity) {
                $entity = $original ? $entity->toOriginalArray() : $entity;
                $values[] = $entity[$primary];
            }
            $this->where($primary, $values);
            return $this;
        }

        foreach ($entities as $entity) {
            $key = [];
            foreach ($primary as $primaryKey) {
                $key[] = $primaryKey . ' = ?';
                /** @var Entity $entity */
                $entity = $original ? $entity->toOriginalArray() : $entity;
                $values[] = $entity[$primaryKey];
            }
            $where[] = implode(' AND ', $key);
        }
        parent::where('(' . implode(') OR (', $where) . ')', ...$values);
        return $this;
    }

    public function getOrder(): array
    {
        return $this->sqlBuilder->getOrder();
    }

    public function search(array $columns, string $search): static
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
     * Returns first result with LIMIT 1 without modifying the query.
     * @return Entity|null
     */
    public function first(): ?Entity
    {
        $offset = $this->sqlBuilder->getOffset();
        return (clone $this)->limit(1, $offset)->fetch();
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
        $offset = $this->sqlBuilder->getOffset() ?? 0;
        $maxOffset = ($limit === null) ? PHP_INT_MAX : ($offset + $limit);
        do {
            $chunk = (clone $this)->limit(min($chunkSize, $maxOffset - $offset), $offset);
            yield $chunk;
            $offset += $chunkSize;
        } while ($offset <= $maxOffset && count($chunk->fetchAll()) >= $chunkSize);
    }

    public function count(?string $column = null): int
    {
        if ($column === null && $this->rows === null && $this->getLimit() === null && $this->getOffset() < 1) {
            $column = '*';
        }
        return parent::count($column);
    }

    /*********************** Getters *************************/

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getEventSubscribers(): RepositoryEventSubscribers
    {
        return $this->repository->getEventSubscribers();
    }

    public function getBehaviors(): RepositoryBehaviors
    {
        return $this->behaviors;
    }

    protected function doesEvents(): bool
    {
        return !$this->behaviors->isScope(RawScope::class);
    }

    /**
     * Ensure that the Query returns the same entity instance for the same primary key.
     */
    protected function createRow(array $row = []): Entity
    {
        $entity = $this->repository->createRow($row, $this);

        $signature = $entity->getSignature(false);
        if ($signature === '') {
            return $entity;
        }

        if (isset($this->entityState[$signature])) {
            $oldEntity = $this->entityState[$signature];
            $oldEntity->internalData($entity->toArray(), false);
            return $oldEntity;
        }
        return $this->entityState[$signature] = $entity;
    }

    public function getLimit(): ?int
    {
        return $this->getSqlBuilder()->getLimit();
    }

    public function getOffset(): ?int
    {
        return $this->getSqlBuilder()->getOffset();
    }

    public function getScope(): Scope
    {
        return $this->behaviors->getScope();
    }

    public function __clone()
    {
        parent::__clone();
        $this->behaviors = clone $this->behaviors;
        $this->entityState = [];
    }

    public function withScope(Scope $scope): static
    {
        $clone = clone $this;
        $clone->behaviors->setScope($scope);
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
