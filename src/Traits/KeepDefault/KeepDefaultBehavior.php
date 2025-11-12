<?php

namespace Efabrica\NetteRepository\Traits\KeepDefault;

use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

/**
 * This behavior will ensure that one row in $query has the $field set to true.
 * Useful for ensuring there is always one row with `is_default` column set to true. (Column name is yours to choose)
 * @see KeepDefaultEventSubscriber
 */
class KeepDefaultBehavior extends RepositoryBehavior
{
    public function __construct(private readonly string $field, private readonly ?Query $query = null)
    {
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getQuery(): ?Query
    {
        return $this->query ? clone $this->query : null;
    }
}
