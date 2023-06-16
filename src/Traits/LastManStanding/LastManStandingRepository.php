<?php

namespace Efabrica\NetteDatabaseRepository\Traits\LastManStanding;

use Efabrica\NetteDatabaseRepository\Repository\Query;

interface LastManStandingRepository
{
    /**
     * @return Query SELECT query that has to always return at least one row before deleting something
     */
    public function lastManQuery(): Query;
}
