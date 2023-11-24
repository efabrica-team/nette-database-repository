<?php

namespace Efabrica\NetteRepository\Repository;

use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Scope\FullScope;
use Efabrica\NetteRepository\Repository\Scope\RawScope;
use Efabrica\NetteRepository\Repository\Scope\Scope;
use Efabrica\NetteRepository\Subscriber\RepositoryEventSubscribers;
use Efabrica\NetteRepository\Traits\RelatedThrough\SetRelatedThroughRepositoryEvent;
use LogicException;
use Nette\Application\BadRequestException;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Arrays;
use PDOException;
use Throwable;

/**
 * @template E of Entity
 * @template Q of Query<E>
 */
abstract class Repository
{
    protected Explorer $explorer;

    private string $tableName;

    /** @var class-string<E> */
    private string $entityClass;

    private RepositoryEventSubscribers $events;

    /** @var class-string<Q> */
    private string $queryClass;

    private RepositoryBehaviors $behaviors;

    private RepositoryManager $manager;

    protected string $findOrFailMessage = 'Entity not found';

    /**
     * @param class-string<E> $entityClass
     * @param class-string<Q> $queryClass
     */
    public function __construct(string $tableName, string $entityClass, string $queryClass, RepositoryDependencies $deps)
    {
        $this->explorer = $deps->getExplorer();
        $this->tableName = $tableName;
        assert(is_a($entityClass, Entity::class, true));
        $this->entityClass = $entityClass;
        assert(is_a($queryClass, Query::class, true));
        $this->queryClass = $queryClass;
        $this->behaviors = new RepositoryBehaviors($this, $deps->getScopeContainer());
        $this->setup($this->behaviors);
        $this->events = clone $deps->getEvents();
        $this->manager = $deps->getManager();
    }

    /**
     * Do $behaviors->add() here.
     */
    abstract protected function setup(RepositoryBehaviors $behaviors): void;

    /**
     * @param Scope $scope
     * @return static cloned
     */
    public function withScope(Scope $scope): self
    {
        $clone = clone $this;
        $clone->behaviors->setScope($scope);
        return $clone;
    }

    /**
     * @return self&static
     */
    public function scopeRaw(): self
    {
        return $this->withScope(new RawScope());
    }

    /**
     * @return self&static
     */
    public function scopeFull(): self
    {
        return $this->withScope(new FullScope());
    }

    /********************************
     * Fetching entities
     ******************************/

    /**
     * @param string|int|array|E $id
     */
    public function find($id): ?Entity
    {
        if ($id instanceof ActiveRow) {
            $id = $id->getPrimary();
        }
        return $this->query()->wherePrimary($id)->limit(1)->fetch();
    }

    /**
     * @return E|null
     */
    public function findOneBy(array $conditions = []): ?Entity
    {
        /** @var E|null $e */
        $e = $this->findBy($conditions)->limit(1)->fetch();
        return $e;
    }

    /**
     * @return Q
     */
    public function findBy(array $conditions): Query
    {
        return $this->query()->where($conditions);
    }

    public function countBy(array $conditions): int
    {
        return $this->findBy($conditions)->count('*');
    }

    /**
     * @return mixed
     */
    public function sumBy(string $column, array $conditions = [])
    {
        return $this->findBy($conditions)->sum($column);
    }

    public function search(array $columns, string $search): Query
    {
        return $this->query()->search($columns, $search);
    }

    /**
     * Makes sure the returned entity is not null and exists.
     * Made to be used in Presenter actions. Throws BadRequestException if not found.
     * @param Entity|string|int|array $id
     */
    public function findOrFail($id): Entity
    {
        $entity = $this->find($id);
        if ($entity instanceof Entity) {
            return $entity;
        }
        throw new BadRequestException($this->findOrFailMessage);
    }

    /**
     * Get the first entity matching the attributes or instantiate it.
     * @param array $conditions Conditions to find by
     * @param array $newValues New values to set if entity is needed to be created
     * @return Entity
     */
    public function findOrNew(array $conditions, array $newValues = []): Entity
    {
        $entity = $this->findOneBy($conditions);
        if ($entity instanceof Entity) {
            return $entity;
        }
        return $this->createRow()->fill($newValues + $conditions);
    }

    /**
     * Get the first entity matching the attributes or create it.
     * @param array $conditions Conditions to find by
     * @param array $newValues New values to set if entity is needed to be created
     * @return Entity inserted or found
     */
    public function findOrInsert(array $conditions, array $newValues = []): Entity
    {
        return $this->findOrNew($conditions, $newValues)->save();
    }

    /********************************
     * Database modifications
     ******************************/

    /**
     * @param E[]|array[]|E|array $entities
     * @return bool|int|ActiveRow
     */
    public function insert(iterable $entities)
    {
        return $this->query()->insert($entities);
    }

    /**
     * @param E|ActiveRow|array|string|int $where Entity, primary value (ID), or array where conditions
     * @param iterable                     $data Data to update
     * @return int Number of affected rows
     */
    public function update($where, iterable $data): int
    {
        $query = $this->query();
        if (is_scalar($where)) {
            return $query->wherePrimary($where)->update($data);
        }
        if ($where instanceof ActiveRow) {
            return $query->update($data, [$where]);
        }
        if (is_array($where)) {
            if (Arrays::isList($where)) {
                return $query->update($data, $where);
            }
            return $query->where($where)->update($data);
        }

        throw new LogicException('Invalid where to update');
    }

    public function updateOrCreate(array $where, array $newValues = []): Entity
    {
        $entity = $this->findOneBy($where);
        if ($entity instanceof Entity) {
            $entity->update($newValues);
            return $entity;
        }
        return $this->createRow($where + $newValues)->save();
    }

    /**
     * Update all entities by their modified values, optimized for least queries
     * @param E&Entity ...$entities
     */
    public function updateEntities(Entity ...$entities): int
    {
        // Group entities by diff to reduce number of queries
        if (count($entities) === 1) {
            $chunks = [$entities];
        } else {
            $chunks = [];
            foreach ($entities as $entity) {
                $diff = $entity->unsavedChanges();
                ksort($diff);
                $found = false;
                /** @var Entity[] $chunk */
                foreach ($chunks as $chunk) {
                    if ($chunk[0]->unsavedChanges() === $diff) {
                        $chunk[] = $entity;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $chunks[] = [$entity];
                }
            }
        }
        $count = 0;
        /** @var Entity[] $chunk */
        foreach ($chunks as $chunk) {
            $count += $this->query()->whereRows(...$chunk)->update($chunk[0]->unsavedChanges());
        }
        return $count;
    }

    public function updateManyToMany(Entity $owner, array $owned, string $ownerColumn, string $ownedColumn): int
    {
        $event = new SetRelatedThroughRepositoryEvent($this, $owner, $owned, $ownerColumn, $ownedColumn);
        return $event->handle();
    }

    /**
     * @param ActiveRow|array|scalar ...$entities
     * @return int
     */
    public function delete(...$entities): int
    {
        foreach ($entities as $entity) {
            if (!$entity instanceof Entity) {
                return $this->query()->whereRows(...$entities)->delete();
            }
        }
        /** @var Entity[] $entities */
        return $this->query()->delete($entities);
    }

    /********************************
     * Getters
     ******************************/

    /**
     * @return Q&Query<E>
     */
    public function query(): Query
    {
        $class = $this->queryClass;
        return new $class($this);
    }

    public function rawQuery(): Query
    {
        return $this->query()->scopeRaw();
    }

    /**
     * @return E[]
     */
    public function fetchAll(): array
    {
        /** @var E[] $fetchAll */
        $fetchAll = $this->query()->fetchAll();
        return $fetchAll;
    }

    public function fetchPairs(?string $key = null, ?string $value = null, ?string $order = null, array $where = []): array
    {
        $query = $this->query()->where($where);
        if ($order !== null) {
            $query->order($order);
        }
        return $query->fetchPairs($key, $value);
    }

    public function getBehaviors(): RepositoryBehaviors
    {
        return $this->behaviors;
    }

    public function getExplorer(): Explorer
    {
        return $this->explorer;
    }

    public function getEventSubscribers(): RepositoryEventSubscribers
    {
        return $this->events;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getManager(): RepositoryManager
    {
        return $this->manager;
    }

    /**
     * @return string[]
     */
    public function getPrimary(): array
    {
        $primary = $this->explorer->getConventions()->getPrimary($this->tableName) ?? [];
        if (!is_array($primary)) {
            return [$primary];
        }
        return array_values($primary);
    }

    /**
     * @return class-string<E>
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @return E
     */
    public function createRow(array $existingData = [], ?QueryInterface $query = null): Entity
    {
        $class = $this->entityClass;
        $query ??= $this->query();
        $entity = new $class($existingData, $query);
        foreach ($query->getEventSubscribers() as $event) {
            $event->onLoad($entity, $this);
        }
        return $entity;
    }

    /*******************************
     * Transactions
     ******************************/

    /**
     * Run new transaction if no transaction is running, do nothing otherwise
     * @template T
     * @param callable(): T $callback
     * @return T
     * @throws Throwable
     */
    final public function transaction(callable $callback)
    {
        $explorer = $this->getExplorer();
        try {
            $inTransaction = $explorer->getConnection()->getPdo()->inTransaction();
            if (!$inTransaction) {
                $explorer->beginTransaction();
            }

            $result = $callback();

            if (!$inTransaction) {
                $explorer->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if (isset($inTransaction) && !$inTransaction && $e instanceof PDOException) {
                $explorer->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @template T
     * @param callable(Repository): T $callback
     * @param int                     $retryTimes
     * @param bool                    $reconnect
     * @return T
     * @throws Throwable
     */
    final public function ensure(callable $callback, int $retryTimes = 3, bool $reconnect = true)
    {
        $attempts = 1;
        while ($attempts < $retryTimes) {
            try {
                return $callback($this);
            } catch (Throwable $e) {
                if ($attempts++ === $retryTimes) {
                    throw $e;
                }
                if ($reconnect) {
                    $this->getExplorer()->getConnection()->reconnect();
                }
            }
        }
        throw new LogicException('Unreachable');
    }

    /*******************************
     * Deprecations
     ******************************/

    /**
     * @deprecated Use query() instead
     * @deprecated instead of overriding, implement SelectEventSubscriber in the repository
     */
    final public function findAll(): Query
    {
        return $this->query();
    }

    /**
     * @deprecated Use getExplorer() instead
     */
    final public function getConnection(): Explorer
    {
        return $this->getExplorer();
    }

    /**
     * @deprecated use rawQuery() instead
     * @deprecated instead of overriding, implement SelectEventSubscriber in the repository
     */
    final public function getTable(): Query
    {
        return $this->rawQuery();
    }

    /**
     * @deprecated use insert() instead
     */
    final public function multiInsert(array $data): int
    {
        $this->query()->insert($data);
        return count($data);
    }

    /**
     * @param callable(): T $callback
     * @param int           $retryTimes
     * @return T
     * @throws Throwable
     * @deprecated use ensure() instead
     * @template T
     */
    final public function retry(callable $callback, int $retryTimes = 2)
    {
        return $this->ensure($callback, $retryTimes, false);
    }

    /**
     * @deprecated use query()->chunks() instead
     */
    final public function chunk(Query $query, ?int $chunkSize, callable $callback, ?int $count = null): void
    {
        if ($count !== null) {
            $query->limit($count, $query->getOffset());
        }
        foreach ($query->chunks($chunkSize ?? Query::CHUNK_SIZE) as $chunk) {
            $callback($chunk);
        }
    }

    public function __clone()
    {
        $this->behaviors = clone $this->behaviors;
    }
}
