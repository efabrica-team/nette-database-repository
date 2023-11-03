<?php

namespace Efabrica\NetteRepository\Repository\Scope;

use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Repository\RepositoryBehaviors;

/**
 * @immutable
 * @readonly
 */
interface Scope
{
    public function apply(RepositoryBehaviors $behaviors): void;
}
