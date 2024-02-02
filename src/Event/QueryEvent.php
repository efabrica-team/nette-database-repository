<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Repository\Entity;
use Efabrica\NetteRepository\Repository\QueryInterface;
use Efabrica\NetteRepository\Repository\RepositoryBehaviors;

abstract class QueryEvent extends RepositoryEvent
{
    protected QueryInterface $query;

    /**
     * @var iterable<Entity>|null
     */
    private ?iterable $entities;

    /**
     * @param iterable<Entity>|null $entities
     */
    public function __construct(QueryInterface $query, ?iterable $entities = null)
    {
        $this->query = $query;
        $this->entities = $entities;
        parent::__construct($query->getRepository());
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
    public function getBehaviors(): RepositoryBehaviors
    {
        return $this->query->getBehaviors();
    }
}
