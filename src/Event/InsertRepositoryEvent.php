<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Repository\Entity;
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
        $this->ended = true;
        $query = $this->getRepository()->rawQuery();
        foreach ($this->entities as $entity) {
            $newRow = $query->insert($entity);
            if ($newRow instanceof ActiveRow) {
                $entity->setInternalData($newRow->toArray());
                $entity->setTable($newRow->getTable());
                if (count($this->entities) === 1) {
                    return new InsertEventResponse($this, $entity);
                }
            }
        }
        return new InsertEventResponse($this, count($this->entities));
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

    public function stopPropagation(): InsertEventResponse
    {
        $this->ended = true;
        return new InsertEventResponse($this, false);
    }
}
