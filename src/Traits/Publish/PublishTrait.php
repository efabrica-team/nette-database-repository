<?php

namespace Efabrica\NetteRepository\Traits\Publish;

use Efabrica\NetteRepository\Repository\Repository;

/**
 * @mixin Repository
 * @implements PublishInterface<*>
 */
trait PublishTrait
{
    public function publish($entity, bool $published = true): int
    {
        /** @var PublishBehavior $publishBehavior */
        $publishBehavior = $this->getBehaviors()->get(PublishBehavior::class, true);
        $publishField = $publishBehavior->getPublishedField();
        return (int)$entity->update([$publishField => $published]);
    }

    public function hide($entity): int
    {
        return $this->publish($entity, false);
    }
}
