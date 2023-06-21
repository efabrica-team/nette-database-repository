<?php

namespace Efabrica\NetteDatabaseRepository\Traits\AutoAlias;

use Efabrica\NetteDatabaseRepository\Model\Entity;

interface AutoAliasRepository
{
    public function setEntityAlias(Entity $entity): string;
}
