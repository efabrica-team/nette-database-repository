<?php

namespace Efabrica\NetteDatabaseRepository\Repository;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Model\EntityManager;
use Efabrica\NetteDatabaseRepository\Model\EntityRelations;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\LoadRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Events;
use Nette\Application\BadRequestException;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection;
use ReflectionClass;
use ReflectionProperty;

/**
 * @template E of Entity
 */
abstract class Repository
{
    protected Explorer $explorer;

    private string $tableName;

    /** @var class-string<E> */
    private string $entityClass;

    private Events $events;

    private EntityRelations $entityRelations;

    private ReflectionProperty $relationsProp;

    /** @var E&Entity */
    private Entity $prototype;

    /**
     * @param class-string<E> $entityClass
     */
    public function __construct(string $tableName, string $entityClass, RepositoryDependencies $deps)
    {
        $this->explorer = $deps->getExplorer();
        $this->tableName = $tableName;
        $refl = new ReflectionClass($entityClass);
        $this->entityClass = $entityClass;
        $this->prototype = $refl->newInstanceWithoutConstructor();
        assert($this->prototype instanceof Entity, 'Entity class must extend ' . Entity::class);
        $this->events = $deps->getEvents()->forRepository($this);
        $this->entityRelations = $deps->getEntityRelations();

        $prop = $refl->getProperty('core');
        $prop->setAccessible(true);
        $this->relationsProp = $prop;
    }

    /**
     * @return Query<E>
     */
    public function query(bool $events = true): Query
    {
        return new Query($this, $this->tableName, $events);
    }

    /**
     * @deprecated Try not to use. Will not be removed.
     */
    public function selection(bool $events): SelectionQuery
    {
        return new SelectionQuery($this->query($events));
    }

    /********************************
     * Fetching entities
     ******************************/
    /**
     * @param string|int|array $id
     */
    public function find($id, bool $events = true): ?Entity
    {
        return $this->query($events)->wherePrimary($id)->fetch();
    }

    public function findOneBy(array $conditions, bool $events = true): ?Entity
    {
        return $this->query($events)->fetch();
    }

    public function findBy($condition = [], ...$params): Query
    {
        return $this->query()->where($condition, ...$params);
    }

    /**
     * @param E&Entity $entity entity to find
     * @param bool     $events whether to fire events
     */
    protected function findByEntity(Entity $entity, bool $events = true): Query
    {
        return $this->query($events)->whereEntity($entity);
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

    /**
     * @return E
     */
    public function toEntity(iterable $data): Entity
    {
        $entity = $this->getPrototype();
        $entity->set($data);
        $this->relationsProp->setValue($entity, clone $this->entityRelations);
        EntityManager::saveOriginal($entity);
        (new LoadRepositoryEvent($this, $entity))->handle();
        return $entity;
    }

    /********************************
     * Database modifications
     ******************************/
    /**
     * @param E&Entity ...$entities
     */
    public function insert(Entity ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->query()->insert($entity);
        }
    }

    /**
     * @param E&Entity $entity
     */
    public function update(Entity $entity): int
    {
        $original = EntityManager::getOriginal($entity);
        $result = $this->findByEntity($entity)->update(array_diff_assoc($entity->toArray(), $original));
        if ($result > 0) {
            EntityManager::saveOriginal($entity);
        }
        return $result;
    }

    /**
     * @param E&Entity $entity
     */
    public function delete(Entity $entity): int
    {
        return $this->findByEntity($entity)->delete();
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

    public function getEntityRelations(): EntityRelations
    {
        return $this->entityRelations;
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
     * @return E&Entity returns a new instance of the entity without calling the constructor and sets the relations property
     */
    public function getPrototype(): Entity
    {
        $prototype = clone $this->prototype;
        $this->relationsProp->setValue($prototype, clone $this->entityRelations);
        return $prototype;
    }
}
