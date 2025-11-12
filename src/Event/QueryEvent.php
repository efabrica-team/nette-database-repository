<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Repository\Entity;
use Efabrica\NetteRepository\Repository\QueryInterface;
use Efabrica\NetteRepository\Repository\RepositoryBehaviors;

abstract class QueryEvent extends RepositoryEvent
{
    /**
     * @param iterable<Entity>|null $entities
     */
    public function __construct(protected QueryInterface $query, private ?iterable $entities = null)
    {
        parent::__construct($this->query->getRepository());
    }

    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    /**
     * @return iterable<Entity> Entities that are affected by this event
     */
    public function getEntities(): iterable
    {
        return $this->entities ??= $this->query;
    }

    /**
     * @return RepositoryBehaviors Ensures behaviors that were removed after query() was called will still be available
     */
    #[\Override]
    public function getBehaviors(): RepositoryBehaviors
    {
        return $this->query->getBehaviors();
    }
}
