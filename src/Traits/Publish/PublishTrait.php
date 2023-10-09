<?php

namespace Efabrica\NetteRepository\Traits\Publish;

use Efabrica\NetteRepository\Repository\Repository;
use Nette\Database\Table\ActiveRow;

/**
 * @mixin Repository
 */
trait PublishTrait
{
    /**
     * @param ActiveRow|int|string $entity
     */
    public function publish($entity): int
    {
        /** @var PublishBehavior $publishBehavior */
        $publishBehavior = $this->getBehaviors()->get(PublishBehavior::class, true);
        $publishField = $publishBehavior->getPublishedField();
        return $this->update($entity, [$publishField => true]);
    }

    /**
     * @param ActiveRow|int|string $entity
     */
    public function hide($entity): int
    {
        /** @var PublishBehavior $publishBehavior */
        $publishBehavior = $this->getBehaviors()->get(PublishBehavior::class, true);
        $publishField = $publishBehavior->getPublishedField();
        return $this->update($entity, [$publishField => false]);
    }
}
