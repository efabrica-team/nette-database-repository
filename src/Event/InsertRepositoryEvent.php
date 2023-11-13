<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Repository;
use Nette\Database\Table\ActiveRow;

/**
 * @extends RepositoryEvent<Entity, InsertEventResponse>
 */
class InsertRepositoryEvent extends RepositoryEvent
{
    /**
     * @var Entity[]
     */
    private array $entities = [];

    /**
     * @param Repository       $repository
     * @param iterable<Entity> $entities
     */
    public function __construct(Repository $repository, iterable $entities)
    {
        parent::__construct($repository);
        foreach ($entities as $entity) {
            $this->entities[] = $entity;
        }
    }

    public function handle(): InsertEventResponse
    {
        while ($subscriber = current($this->subscribers)) {
            next($this->subscribers);
            if ($subscriber->supportsEvent($this)) {
                return $subscriber->onInsert($this);
            }
        }
        $query = $this->getRepository()->rawQuery();
        foreach ($this->entities as $entity) {
            $newRow = $query->insert($entity);
            if ($newRow instanceof ActiveRow) {
                $entity->internalData($newRow->toArray(), false);
                if (count($this->entities) === 1) {
                    return $this->stopPropagation($entity);
                }
            }
        }
        return $this->stopPropagation(count($this->entities));
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
