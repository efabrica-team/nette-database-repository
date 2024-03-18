<?php

namespace Efabrica\NetteRepository\Traits\Publish;

use Efabrica\NetteRepository\Repository\Entity;

interface PublishInterface
{
    /**
     * @param Entity|scalar $entity
     */
    public function publish($entity, bool $published = true): int;

    /**
     * @param Entity|scalar $entity
     */
    public function hide($entity): int;
}
