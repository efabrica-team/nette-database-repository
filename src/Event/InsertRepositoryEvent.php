<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Repository;
use LogicException;
use Nette\Database\Table\ActiveRow;
use Traversable;

/**
 * @extends RepositoryEvent<Entity, InsertEventResponse>
 */
class InsertRepositoryEvent extends RepositoryEvent
{
    /**
     * @var iterable<Entity> (can be array or Selection)
     */
    private iterable $entities;

    /**
     * @param Repository $repository
     * @param iterable<Entity>   $entities
     */
    public function __construct(Repository $repository, iterable $entities)
    {
        parent::__construct($repository);
        $this->entities = $entities instanceof Traversable ? iterator_to_array($entities) : $entities;
    }

    public function handle(): InsertEventResponse
    {
        while ($subscriber = current($this->subscribers)) {
            next($this->subscribers);
            if ($subscriber->supportsEvent($this)) {
                return $subscriber->onInsert($this);
            }
        }
        $entities = [];
        foreach ($this->entities as $entity) {
            $entities[] = $entity->toArray();
        }

        $result = $this->getRepository()->rawQuery()->insert($entities);
        if ($result instanceof Entity) {
            $this->entities = [$result];
        } elseif (is_int($result)) {
            $this->entities = $this->getRepository()->rawQuery()->whereRows(...$entities);
        } else {
            throw new LogicException('Insert query must return entity or int, returned ' . gettype($result));
        }

        return $this->stopPropagation($result);
    }

    /**
     * @return iterable<Entity>
     */
    public function getEntities(): iterable
    {
        return $this->entities;
    }

    public function addEntity(Entity $entity): void
    {
        $entityClass = $this->getRepository()->getEntityClass();
        assert(is_a($entity, $entityClass));
        $this->entities[] = $entity;
    }

    public function removeEntity(Entity $entity): void
    {
        $key = array_search($entity, $this->entities, true);
        if ($key !== false) {
            unset($this->entities[$key]);
        }
    }

    /**
     * @param bool|int|ActiveRow $response
     */
    public function stopPropagation($response = false): InsertEventResponse
    {
        $this->subscribers = [];
        return new InsertEventResponse($this, $response);
    }
}
