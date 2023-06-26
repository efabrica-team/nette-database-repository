<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber\Event;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Query;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

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

    /**
     * @return Query<Entity>
     */
    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * @return Entity[]
     */
    public function getEntities(): array
    {
        return $this->entities ??= $this->query->fetchAll();
    }
}
