<?php

namespace Efabrica\NetteRepository\Traits\LastManStanding;

use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class LastManStandingBehavior extends RepositoryBehavior
{
    private Query $query;

    /**
     * @param Query $query SELECT query that will be enforced to always return at least one row
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
