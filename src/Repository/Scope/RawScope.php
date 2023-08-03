<?php

namespace Efabrica\NetteRepository\Repository\Scope;

use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Repository\RepositoryBehaviors;

/**
 * This scope removes all behaviors.
 */
final class RawScope implements Scope
{
    public function apply(RepositoryBehaviors $behaviors, Repository $repository): void
    {
        $behaviors->removeAll();
    }
}
