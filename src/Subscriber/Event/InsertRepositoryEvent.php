<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber\Event;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

/**
 * @extends RepositoryEvent<Entity, InsertEntityEventResponse>
 */
class InsertRepositoryEvent extends RepositoryEvent
{
    /**
     * @var Entity[]
     */
    private array $entities;

    /**
     * @param Repository $repository
     * @param Entity[] $entities
     */
    public function __construct(Repository $repository, array $entities)
    {
        parent::__construct($repository);
        $this->entities = $entities;
    }

    public function handle(): InsertEntityEventResponse
    {
        $subscriber = current($this->subscribers);
        next($this->subscribers);
        if ($subscriber instanceof EventSubscriber) {
            return $subscriber->onInsert($this);
        }
        return $this->stopPropagation(
            $this->getRepository()->selection(false)->insert($this->entities)
        );
    }

    /**
     * @return Entity[]
     */
    public function getEntities(): array
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

    public function stopPropagation($response = null): InsertEntityEventResponse
    {
        $this->subscribers = [];
        return new InsertEntityEventResponse($this, $response);
    }
}
