<?php

namespace Efabrica\NetteRepository\Traits\SoftDelete;

use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Repository;

/**
 * @mixin Repository
 */
trait SoftDeleteTrait
{
    public function forceDelete(Entity $entity): int
    {
        return $this->query(false)->where($entity)->delete();
    }

    public function restore(Entity $entity): int
    {
        /** @var SoftDeleteBehavior $behavior */
        $behavior = $this->behaviors()->get(SoftDeleteBehavior::class);
        return $this->update($entity, [$behavior->getColumn() => null]);
    }
}
