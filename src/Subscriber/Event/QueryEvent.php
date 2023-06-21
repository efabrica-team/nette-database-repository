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

    public function __construct(Query $query)
    {
        $this->query = $query;
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
}
