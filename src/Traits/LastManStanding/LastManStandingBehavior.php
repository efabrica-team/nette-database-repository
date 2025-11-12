<?php

namespace Efabrica\NetteRepository\Traits\LastManStanding;

use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class LastManStandingBehavior extends RepositoryBehavior
{
    /**
     * @param Query $query SELECT query that will be enforced to always return at least one row
     */
    public function __construct(private readonly Query $query)
    {
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
