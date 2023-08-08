<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Repository\QueryInterface;
use Efabrica\NetteRepository\Repository\RepositoryBehaviors;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;

abstract class QueryEvent extends RepositoryEvent
{
    protected QueryInterface $query;

    /**
     * @var iterable<Entity>|null
     */
    private $entities;

    /**
     * @param iterable<Entity>|null  $entities
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
        /** @var iterable<Entity> $query */
        $query = $this->query;
        return $this->entities ??= $query;
    }

    /**
     * @return RepositoryBehaviors Ensures behaviors that were removed after query() was called will still be available
     */
    public function getBehaviors(): RepositoryBehaviors
    {
        return $this->query->getBehaviors();
    }
}
