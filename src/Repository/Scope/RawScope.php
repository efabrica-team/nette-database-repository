<?php

namespace Efabrica\NetteRepository\Repository\Scope;

use Efabrica\NetteRepository\Repository\RepositoryBehaviors;

/**
 * This scope removes all behavior and disables all event subscribers
 */
final class RawScope implements Scope
{
    public function apply(RepositoryBehaviors $behaviors): void
    {
        $behaviors->removeAll();
    }
}
