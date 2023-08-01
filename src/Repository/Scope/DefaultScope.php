<?php

namespace Efabrica\NetteRepository\Repository\Scope;

use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Repository\RepositoryBehaviors;

class DefaultScope implements Scope
{
    public function apply(RepositoryBehaviors $behaviors, Repository $repository): void
    {
    }
}
