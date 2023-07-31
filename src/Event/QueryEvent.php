<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Repository\RepositoryBehaviors;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;

abstract class QueryEvent extends RepositoryEvent
{
    protected Query $query;

    /**
     * @var EventSubscriber[]
     */
    protected array $subscribers;

    /**
     * @var Entity[]|null
     */
    private ?array $entities;

    public function __construct(Query $query, ?array $entities = null)
    {
        $this->query = $query;
        $this->entities = $entities;
        $this->subscribers = $query->getEvents()->toArray();
        parent::__construct($query->getRepository());
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * @return Entity[]
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
