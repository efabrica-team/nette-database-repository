<?php

namespace Efabrica\NetteDatabaseRepository\Traits\AutoAlias;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Repository;

/**
 * @mixin Repository
 */
trait AutoAliasBehavior
{
    abstract public function setEntityAlias(Entity $entity): string;
}
