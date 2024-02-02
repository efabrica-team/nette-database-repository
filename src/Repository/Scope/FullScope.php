<?php

namespace Efabrica\NetteRepository\Repository\Scope;

use Efabrica\NetteRepository\Repository\RepositoryBehaviors;

/**
 * This scope lets all behaviors enabled.
 */
final class FullScope implements Scope
{
    public function apply(RepositoryBehaviors $behaviors): void
    {
    }
}
