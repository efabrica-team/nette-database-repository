<?php

namespace Efabrica\NetteDatabaseRepository\Repository;

use Efabrica\NetteDatabaseRepository\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Subscriber\Events;
use LogicException;
use Nette\Application\BadRequestException;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
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

    private Events $events;

    /** @var class-string<Q> */
    private string $queryClass;

    /**
     * @param class-string<E> $entityClass
     * @param class-string<Q> $queryClass
     */
    public function __construct(string $tableName, string $entityClass, string $queryClass, RepositoryDependencies $deps)
    {
        $this->explorer = $deps->getExplorer();
        $this->tableName = $tableName;
        $this->events = $deps->getEvents()->forRepository($this);
        assert(is_a($entityClass, Entity::class, true));
        $this->entityClass = $entityClass;
        assert(is_a($queryClass, Query::class, true));
        $this->queryClass = $queryClass;
    }

    /**
     * @param bool $events
     * @return Query<E>&Q
     */
    public function query(bool $events = true): Query
    {
        return new ($this->queryClass)($this, $events);
    }

    /********************************
     * Fetching entities
     ******************************/

    /**
     * @param string|int|array|E $id
     */
    public function find($id, bool $defaultWhere = true): ?Entity
    {
        if ($id instanceof ActiveRow) {
            $id = $id->getPrimary();
        }
        return $this->query($defaultWhere)->wherePrimary($id)->limit(1)->fetch();
    }

    /**
     * @return E|null
     */
    public function findOneBy(array $conditions, bool $defaultWhere = true): ?Entity
    {
        return $this->query($defaultWhere)->where($conditions)->limit(1)->fetch();
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
        return $this->findBy($conditions)->count();
    }

    public function sumBy(string $column, array $conditions = []): int
    {
        return $this->findBy($conditions)->sum($column);
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
     */
    public function insert(Entity ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->query()->insert($entity);
        }
    }

    /**
     * @param E&Entity ...$entities
     */
    public function update(Entity ...$entities): int
    {
        // Group entities by diff to reduce number of queries
        if (count($entities) === 1) {
            $chunks = [$entities];
        } else {
            $chunks = [];
            foreach ($entities as $entity) {
                $diff = $entity->diff();
                ksort($diff);
                $chunks[serialize($diff)][] = $entity;
            }
        }
        /** @var Entity[] $chunk */
        foreach ($chunks as $chunk) {
            $this->query()->where($chunk)->update($chunk[0]->diff());
        }
        return count($entities);
    }

    /**
     * @deprecated Use Entity setters ideally or query()->update()
     * @param array $conditions
     * @param array $values
     * @return int
     */
    public function updateBy(array $conditions, array $values): int
    {
        return $this->query()->where($conditions)->update($values);
    }

    /**
     * @param E&Entity ...$entities
     */
    public function delete(Entity ...$entities): int
    {
        $query = $this->query()->where($entities);
        return (new DeleteQueryEvent($query, $entities))->handle();
    }

    /********************************
     * Getters
     ******************************/
    public function getExplorer(): Explorer
    {
        return $this->explorer;
    }

    public function getEvents(): Events
    {
        return $this->events;
    }

    public function getTableName(): string
    {
        return $this->tableName;
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
        return $primary;
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
        $entity = new ($this->entityClass)($row, $this->query());
        $events = $query !== null ? $query->getEvents() : $this->getEvents();
        foreach ($events as $event) {
            $event->onCreate($entity);
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
     * @param callable(): T $callback
     * @param int           $retryTimes
     * @param bool          $reconnect
     * @return T
     * @throws Throwable
     */
    final public function retry(callable $callback, int $retryTimes = 3, bool $reconnect = true)
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
     * @deprecated use query(false) instead
     * @deprecated instead of overriding, implement SelectEventSubscriber in the repository
     */
    final public function getTable(): Query
    {
        return $this->query(false);
    }

    /**
     * @deprecated use query()->fetchPairs() instead
     */
    final public function fetchPairs(string $key, ?string $value = null, ?string $order = null, array $where = []): array
    {
        $query = $this->query()->where($where);
        if ($order !== null) {
            $query->order($order);
        }
        return $query->fetchPairs($key, $value);
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
     * @deprecated use retry() instead
     * @template T
     */
    final public function ensure(callable $callback, int $retryTimes = 2)
    {
        return $this->retry($callback, $retryTimes);
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
