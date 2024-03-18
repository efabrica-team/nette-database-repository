<?php

namespace Efabrica\NetteRepository\Traits\RelatedThrough;

use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEventResponse;
use Efabrica\NetteRepository\Repository\Query;

class GetRelatedEventResponse extends RepositoryEventResponse
{
    private Query $query;

    public function __construct(RepositoryEvent $event, Query $query)
    {
        parent::__construct($event);
        $this->query = $query;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public function setQuery(Query $query): self
    {
        $this->query = $query;
        return $this;
    }
}
