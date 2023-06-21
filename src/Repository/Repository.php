<?php

namespace Efabrica\NetteDatabaseRepository\Repository;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Subscriber\Events;
use Nette\Application\BadRequestException;
use Nette\Database\Explorer;

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
    public function find($id, bool $events = true): ?Entity
    {
        if ($id instanceof Entity) {
            $id = $id->getPrimary();
        }
        return $this->query($events)->wherePrimary($id)->fetch();
    }

    /**
     * @return E|null
     */
    public function findOneBy(array $conditions, bool $events = true): ?Entity
    {
        return $this->query($events)->limit(1)->fetch();
    }

    /**
     * @param array<int|string,string>|(E&Entity) $condition
     * @param mixed                      ...$params
     * @return Q&Query<E>
     */
    public function findBy($condition = [], ...$params): Query
    {
        return $this->query()->where($condition, ...$params);
    }

    /**
     * Makes sure the returned entity is not null and exists.
     * Made to be used in presenter actions. Throws BadRequestException if not found.
     * @param Entity|string|int|array $id
     */
    public function look($id): Entity
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
        $i = 0;
        foreach ($entities as $entity) {
            $i += $this->findBy($entity)->update($entity->diff());
        }
        return $i;
    }

    /**
     * @param E&Entity $entity
     */
    public function delete(Entity $entity): int
    {
        return $this->findBy($entity)->delete();
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
     * @return class-string<E>
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @return E
     */
    public function createRow(): Entity
    {
        $class = $this->entityClass;
        return new $class([], $this->query());
    }
}
