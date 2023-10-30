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
     * @param Repository       $repository
     * @param iterable<Entity> $entities
     */
    public function __construct(Repository $repository, iterable $entities)
    {
        parent::__construct($repository);
        $this->entities = array_values($entities instanceof Traversable ? iterator_to_array($entities) : $entities);
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
        return $this->stopPropagation($this->updateEntities($result, $entities));
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
        if (!is_array($this->entities)) {
            $this->entities = iterator_to_array($this->entities);
        }
        $this->entities[] = $entity;
    }

    public function removeEntity(Entity $entity): void
    {
        if (!is_array($this->entities)) {
            $this->entities = iterator_to_array($this->entities);
        }
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

    /**
     * @param mixed $result
     * @return Entity|int
     */
    protected function updateEntities($result, array $entities)
    {
        if (count($entities) > 1) {
            $i = 0;
            /** @var Entity $newEntity */
            foreach ($this->getRepository()->rawQuery()->whereRows(...$entities)->fetchChunked() as $newEntity) {
                $this->entities[$i++]->internalData($newEntity, false);
            }
            return $result;
        }
        if ($result instanceof Entity) {
            $this->entities[0]->internalData($result, false);
            return $this->entities[0];
        }
        throw new LogicException('Insert query must return entity for single insert, returned ' . gettype($result));
    }
}
