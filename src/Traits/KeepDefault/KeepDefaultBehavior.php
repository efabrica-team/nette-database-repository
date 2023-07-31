<?php

namespace Efabrica\NetteRepository\Traits\KeepDefault;

use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

/**
 * This behavior will ensure at least one row in $query has the $field set to true.
 * Useful for ensuring there is always one row with `is_default` column set to true. (Column name is yours to choose)
 * @see KeepDefaultEventSubscriber
 */
class KeepDefaultBehavior extends RepositoryBehavior
{
    private string $field;

    private ?Query $query;

    public function __construct(string $field, ?Query $query = null)
    {
        $this->field = $field;
        $this->query = $query;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getQuery(): ?Query
    {
        return $this->query;
    }
}
