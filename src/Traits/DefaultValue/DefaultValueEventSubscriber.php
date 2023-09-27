<?php

namespace Efabrica\NetteRepository\Traits\DefaultValue;

use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Repository\RepositoryBehaviors;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;

final class DefaultValueEventSubscriber extends EventSubscriber
{
    public function supportsEvent(RepositoryEvent $event): bool
    {
        return $event->hasBehavior(DefaultValueBehavior::class);
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        foreach ($event->getBehaviors()->all() as $behavior) {
            if ($behavior instanceof DefaultValueBehavior) {
                $field = $behavior->getField();
                $value = $behavior->getValue();
                foreach ($event->getEntities() as $entity) {
                    if (!isset($entity[$field])) {
                        $entity[$field] = $value;
                    }
                }
            }
        }
        return $event->handle();
    }
}
