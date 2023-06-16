<?php

namespace Efabrica\NetteDatabaseRepository\Traits\SoftDelete;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use LogicException;

/**
 * @mixin Repository
 */
trait SoftDeleteRepositoryTrait
{
    public function forceDelete(Entity $entity): int
    {
        return $this->findByEntity($entity, false)->delete();
    }

    public function restore(Entity $entity): int
    {
        $e = $this->getEvents()->get(SoftDeleteEventSubscriber::class);
        if ($e instanceof SoftDeleteEventSubscriber && $this instanceof Repository) {
            return $e->restore($this, $entity);
        }
        throw new LogicException('Soft delete is not supported by this repository.');
    }
}
