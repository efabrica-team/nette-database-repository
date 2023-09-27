<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Repository;
use Nette\Database\Table\ActiveRow;
use Traversable;

/**
 * @extends RepositoryEvent<Entity, InsertEventResponse>
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
        return $this->stopPropagation(
            $this->getRepository()->rawQuery()->insert($entities)
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

    /**
     * @param bool|int|ActiveRow|null $response
     * @return InsertEventResponse
     */
    public function stopPropagation($response = null): InsertEventResponse
    {
        $this->subscribers = [];
        return new InsertEventResponse($this, $response);
    }
}
