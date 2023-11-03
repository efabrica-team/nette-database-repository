<?php

namespace Efabrica\NetteRepository\Traits\Publish;

use Efabrica\NetteRepository\Repository\Repository;
use Nette\Database\Table\ActiveRow;

/**
 * @mixin Repository
 * @implements PublishInterface
 */
trait PublishTrait
{
    /**
     * @param ActiveRow|int|string $entity
     */
    public function publish($entity, bool $published = true): int
    {
        /** @var PublishBehavior $publishBehavior */
        $publishBehavior = $this->getBehaviors()->get(PublishBehavior::class, true);
        $publishField = $publishBehavior->getPublishedField();
        return (int)$entity->update([$publishField => $published]);
    }

    /**
     * @param ActiveRow|int|string $entity
     */
    public function hide($entity): int
    {
        return $this->publish($entity, false);
    }
}
