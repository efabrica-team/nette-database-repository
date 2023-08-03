<?php

namespace Efabrica\NetteRepository\Repository;

use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Scope\Scope;
use Efabrica\NetteRepository\Subscriber\RepositoryEvents;
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

    private RepositoryEvents $events;

    /** @var class-string<Q> */
    private string $queryClass;

    private RepositoryBehaviors $behaviors;

    private RepositoryManager $manager;

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
        $this->events = $deps->getEvents()->forRepository($this);
        $this->manager = $deps->getManager();
    }

    /**
     * Do $behaviors->add() here.
     */
    abstract protected function setup(RepositoryBehaviors $behaviors): void;

    public function setScope(Scope $scope): self
    {
        $this->behaviors->setScope($scope);
        return $this;
    }

    public function scopeRaw(): self
    {
        return $this->setScope($this->behaviors->getScope()->raw());
    }

    public function scopeFull(): self
    {
        return $this->setScope($this->behaviors->getScope()->full());
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
    public function findOneBy(array $conditions): ?Entity
    {
        return $this->findBy($conditions)->limit(1)->fetch();
    }

    /**
     * @return Q&Query<E>
     */
    public function findBy(array $conditions): Query
    {
        return $this->query()->where($conditions);
    }

    public function countBy(array $conditions): int
    {
        return $this->findBy($conditions)->count('*');
    }

    public function sumBy(string $column, array $conditions = []): int
    {
        return $this->findBy($conditions)->sum($column);
    }

    public function search(array $columns, string $search): Query
    {
        return $this->query()->search($columns, $search);
    }

    /**
     * Makes sure the returned entity is not null and exists.
     * Made to be used in presenter actions. Throws BadRequestException if not found.
     * @param Entity|string|int|array $id
     */
    public function lookup($id): Entity
    {
        if ($id instanceof Entity) {
            return $id;
        }
        $entity = $this->find($id);
        if ($entity) {
            return $entity;
        }
        throw new BadRequestException('Entity not found');
    }

    /********************************
     * Database modifications
     ******************************/

    /**
     * @param E ...$entities
     * @return E|int
     */
    public function insert(iterable ...$entities)
    {
        return $this->query()->insert($entities);
    }

    /**
     * @param E|ActiveRow|array|string|int $row Entity, primary value (ID), or array where conditions
     * @param iterable           $data Data to update
     * @return int Number of affected rows
     */
    public function update($row, iterable $data): int
    {
        $query = $this->query();
        if (is_scalar($row)) {
            $query->wherePrimary($row);
        } elseif ($row instanceof ActiveRow) {
            $query->wherePrimary($row->getPrimary());
        } elseif (is_array($row)) {
            if (Arrays::isList($row)) {
                assert(reset($row) instanceof ActiveRow);
                $query->whereRows($row);
            } else {
                $query->where($row);
            }
        } else {
            throw new LogicException('Invalid row to update');
        }
        return $query->update($data);
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
                $diff = $entity->diff();
                ksort($diff);
                $found = false;
                foreach ($chunks as $chunk) {
                    if ($chunk[0]->diff() === $diff) {
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
            $count += $this->query()->whereRows($chunk)->update($chunk[0]->diff());
        }
        return $count;
    }

    public function delete(iterable ...$entities): int
    {
        $query = $this->query()->whereRows(...$entities);
        return (new DeleteQueryEvent($query, $entities))->handle();
    }

    /********************************
     * Getters
     ******************************/

    /**
     * @return Q
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
     * @return iterable<E>
     */
    public function fetchAll(): iterable
    {
        return $this->query()->fetchAll();
    }

    public function fetchPairs(?string $key = null, ?string $value = null, ?string $order = null, array $where = []): array
    {
        $query = $this->query()->where($where);
        if ($order !== null) {
            $query->order($order);
        }
        return $query->fetchPairs($key, $value);
    }

    public function behaviors(): RepositoryBehaviors
    {
        return $this->behaviors;
    }

    public function getExplorer(): Explorer
    {
        return $this->explorer;
    }

    public function getEvents(): RepositoryEvents
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
        $primary = $this->explorer->getConventions()->getPrimary($this->tableName);
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
    public function createRow(array $row = [], ?Query $query = null): Entity
    {
        $class = $this->entityClass;
        $entity = new $class($row, $query ?? $this->query());
        $events = $query !== null ? $query->getEvents() : $this->getEvents();
        foreach ($events as $event) {
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
    final public function chunk(Query $query, ?int $limit, callable $callback, ?int $count = null): void
    {
        foreach ($query->chunks($limit) as $chunk) {
            $callback($chunk);
        }
    }
}
